<?php

namespace Larapress\Resources;

use As247\WpEloquent\Database\Eloquent\Model;
use Exception;
use Larapress\Components\Form\Form;
use Larapress\Dependencies\BaseElement;

abstract class BaseResource extends BaseElement
{
    public array $data = [];
    public string $capability = 'edit_posts';
    public bool $showMenu = false;

    public string $action = '';

    public ?Model $entry = null;

    protected string $separator = '_';
    private string $adminPost = '';
    protected string $formView = BASE_PATH.'/vendor/larapress/core/resources/Views/form.php';

    /**
     * @throws Exception
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'generateRoutes']);
        $this->generateActions();

        $this->adminPost = get_admin_url().'admin-post.php';
    }

    protected function redirect($slug)
    {
        $route = 'admin.php?page='.$slug;
        wp_redirect(admin_url($route));
        die();
    }

    public function generateRoutes(): void
    {
        $pages = $this->getPages();
        $parentSlug = $this->slugify(config('app.slug'), $this->getClass());
        $position = 1;
        if ($this->showMenu){
            $firstAction = array_keys($pages)[0];

            add_menu_page(
                $this->getMenuTitle(),
                $this->getMenuTitle(),
                $this->capability,
                $parentSlug,
                $pages[$firstAction],
                $this->getMenuIcon(),
                $this->getPosition(),
            );
            add_submenu_page(
                $parentSlug,
                $this->getMenuTitle(),
                $this->getMenuTitle(),
                $this->capability,
                $parentSlug,
                $pages[$firstAction],
                $position
            );

            $position++;
            array_shift($pages);
        }

        foreach ($pages as $action => $page) {
            $slug = $this->slugify($parentSlug,$action);
            $pageTitle = $this->showMenu? ucfirst($action) . ' ' . $this->getClass():'';

            add_submenu_page(
                $parentSlug,
                $pageTitle,
                ucfirst($action),
                $this->capability,
                $slug,
                $page,
                $position
            );

            $position++;
        }
    }

    /**
     * @throws Exception
     */
    public function generateActions(): void
    {
        $actions = $this->getActions();

        foreach ($actions as $action => $details) {
            if (!isset($details['type']) || !isset($details['action'])) {
                throw new Exception("Action $action doesn't exist");
            }

            $hook = $this->slugify($details['type'], config('app.slug'), $this->getClass(), $action);

            // The guard runs before the resource's own handler, so a handler
            // can never see a request that failed the nonce or capability
            // check. Without this, every admin_post_* handler is CSRF-open
            // unless its author remembers to check — and they usually do not.
            add_action($hook, function () use ($action, $details) {
                $this->authorizeAction($action, $details);
            }, 1);

            add_action($hook, $details['action']);
        }
    }

    /**
     * Verifies capability and nonce for an action request, dying on failure.
     *
     * Opt out for a deliberately public endpoint by setting
     * ['public' => true] on the action, which is then responsible for its
     * own validation.
     */
    protected function authorizeAction(string $action, array $details): void
    {
        if (!empty($details['public'])) {
            return;
        }

        $capability = $details['capability'] ?? $this->capability;

        if (!current_user_can($capability)) {
            wp_die(
                esc_html__('You are not allowed to do this.', 'larapress'),
                '',
                ['response' => 403]
            );
        }

        $nonce = $this->nonceAction($action);

        // check_admin_referer() accepts the referer alone when the nonce field
        // is absent, which is too weak here, so require the nonce itself.
        $supplied = $_REQUEST['_wpnonce'] ?? '';

        if (!$supplied || !wp_verify_nonce($supplied, $nonce)) {
            wp_nonce_ays($nonce);
        }
    }

    /** Nonce action for a resource action; pair it with nonceField(). */
    public function nonceAction(string $action = 'save'): string
    {
        return $this->slugify(config('app.slug'), $this->getClass(), $action, 'nonce');
    }

    /**
     * Hidden nonce input for a form posting to one of this resource's actions.
     *
     * @throws Exception
     */
    public static function nonceField(string $action = 'save'): string
    {
        $instance = new static();

        return wp_nonce_field($instance->nonceAction($action), '_wpnonce', true, false);
    }

    /**
     * URL for one of this resource's actions, nonce included.
     *
     * @throws Exception
     */
    public static function actionUrl(string $action = 'save', array $params = []): string
    {
        $instance = new static();

        $url = add_query_arg(
            array_merge(['action' => static::getAction($action)], $params),
            admin_url('admin-post.php')
        );

        return wp_nonce_url($url, $instance->nonceAction($action));
    }

    /**
     * @throws Exception
     */
    protected static function getRoute($action = 'create', $params = []): string
    {
        $queryParams = http_build_query($params);
        $instance = new static();
        $pages = $instance->getPages();

        if (!isset($pages[$action])) {
            throw new Exception("Route $action doesn't exist");
        }

        return admin_url('admin.php?page=' . $instance->slugify(config('app.slug'), $instance->getClass(), $action).'&'.$queryParams);
    }

    /**
     * @throws Exception
     */
    protected static function getAction($action = 'save')
    {
        $instance = new static();
        $actions = $instance->getActions();

        if (!isset($actions[$action])) {
            throw new Exception("Action $action doesn't exist");
        }

        return $instance->slugify(config('app.slug'), $instance->getClass(),$action);
    }

    /**
     * @throws Exception
     */
    protected function render(): void
    {
        $formInstance = new Form();
        $form = $this->form($formInstance);


        if($this->entry != null) {
            if(!@$_SESSION['errors'] || !@$_SESSION['form_data']) {
                $form->fill($this->entry);
            }
            $idField = '<input type="hidden" name="id" value="'.$this->entry->getKey().'"></input>';
        }else{
            $idField = '';
        }

        if(@$_SESSION['errors']){
            if (@$_SESSION['form_data']) {
                $form->fill($_SESSION['form_data']);
                unset($_SESSION['form_data']);
            }
        }

        $adminPost= $this->adminPost;
        $action = $this->action;

        // $action is the full admin_post hook; the nonce is keyed on the bare
        // action name, which is its last segment.
        $actionName = $action === ''
            ? 'save'
            : substr($action, strrpos($action, $this->separator) + 1);

        $nonceField = wp_nonce_field($this->nonceAction($actionName), '_wpnonce', true, false);

        add_action('wp_enqueue_scripts', [$this, 'enqueue']);

        require_once $this->formView;

    }

    protected function setEntry(Model $model): static
    {
        $this->entry = $model;
        return $this;
    }

    public function goBack(): void
    {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    protected function getCurrentAction()
    {
        $action = $_POST['action']??null;
        if($action){
            $action = explode($this->separator, $action)[count(explode($this->separator, $action))-1];
        }
        return $action;
    }

    public function getMenuTitle():?string
    {
        return null;
    }

    public function getMenuIcon():?string
    {
        return null;
    }

    public function getPosition(): ?int
    {
        return null;
    }
}

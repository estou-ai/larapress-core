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
            $pageTitle = $this->showMenu? ucfirst($action) . ' BaseResource.php' .$this->getClass():'';

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

            add_action($this->slugify($details['type'], config('app.slug'), $this->getClass(),$action), $details['action']);
        }
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

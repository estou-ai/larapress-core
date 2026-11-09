<?php

namespace Larapress\Resources;

use As247\WpEloquent\Database\Eloquent\Model;
use Exception;
use Larapress\Components\Form\Form;
use Larapress\Dependencies\BaseElement;
use Rakit\Validation\Validator;

abstract class BaseResource extends BaseElement
{
    public array $data = [];

    /**
     * manage_options, not edit_posts: this default gates the admin pages and
     * every mutating action, and edit_posts is held by Contributors — the
     * weakest default would hand them data listings and delete links.
     * Widen deliberately per resource when a page really is for authors.
     */
    public string $capability = 'manage_options';
    public bool $showMenu = false;

    public string $action = '';

    /** The declared action key for the request being served, set by the guard. */
    private ?string $currentActionKey = null;

    /** Full admin_post hook => declared action key, for render()/submitted(). */
    private static array $actionKeys = [];

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
        $parentSlug = $this->menuParent() ?? $this->slugify(config('app.slug'), $this->getClass());
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
                $this->pageLabel($firstAction),
                $this->capability,
                $parentSlug,
                $pages[$firstAction],
                $position
            );

            $position++;
            array_shift($pages);
        }

        foreach ($pages as $action => $page) {
            $slug = $this->slugify(
                $this->slugify(config('app.slug'), $this->getClass()),
                $action
            );

            // A hidden page registers under no parent: WordPress still routes
            // it and still enforces the capability, it simply gets no sidebar
            // entry. remove_submenu_page() would drop the authorisation entry
            // too, answering 403 on every visit.
            if (in_array($action, $this->hiddenPages(), true)) {
                add_submenu_page(
                    '',
                    $this->pageLabel($action),
                    '',
                    $this->capability,
                    $slug,
                    $page
                );

                continue;
            }

            add_submenu_page(
                $parentSlug,
                $this->pageLabel($action),
                $this->pageLabel($action),
                $this->capability,
                $slug,
                $page,
                $this->menuParent() ? $this->getPosition() ?? $position : $position
            );

            $position++;
        }
    }

    /**
     * Menu slug to hang these pages under, for a resource that belongs in
     * another resource's menu rather than carrying its own.
     *
     * Return a parent and leave $showMenu false to add submenus only.
     */
    public function menuParent(): ?string
    {
        return null;
    }

    /**
     * Submenu label for a page key.
     *
     * Defaults to the capitalised key, which is only ever right in English;
     * override menuLabels() to name them properly.
     */
    protected function pageLabel(string $action): string
    {
        return $this->menuLabels()[$action] ?? ucfirst($action);
    }

    /**
     * Human labels per page key: ['list' => __('Events'), ...].
     *
     * @return array<string,string>
     */
    public function menuLabels(): array
    {
        return [];
    }

    /**
     * Page keys that register without a menu entry.
     *
     * @return string[]
     */
    public function hiddenPages(): array
    {
        return [];
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

            // Remember which declared key this hook serves. render() and
            // getCurrentAction() used to take the last `_`-delimited segment
            // of the hook, which mangled snake_case keys ('delete_all' became
            // 'all'): the form emitted a nonce for the wrong action, and
            // submitted() then found no rules for 'all' and silently returned
            // raw, unvalidated POST to the handler.
            self::$actionKeys[$hook] = $action;

            // The guard runs before the resource's own handler, so a handler
            // can never see a request that failed the nonce or capability
            // check. Without this, every admin_post_* handler is CSRF-open
            // unless its author remembers to check — and they usually do not.
            add_action($hook, function () use ($action, $details) {
                $this->currentActionKey = $action;
                $this->authorizeAction($action, $details);
            }, 1);

            add_action($hook, $details['action']);
        }
    }

    /** The declared action key behind a hook, when generateActions() saw it. */
    public static function resolveActionKey(string $hook): ?string
    {
        return self::$actionKeys[$hook] ?? null;
    }

    /**
     * Verifies capability and nonce for an action request, dying on failure.
     *
     * Opt out for a deliberately public endpoint by setting
     * ['public' => true] on the action, which is then responsible for its
     * own validation.
     *
     * Actions answer POST by default. A nonce in a GET query string leaks
     * through Referer headers, browser history and server logs, so a
     * link-style action (an export, a link in a table row) must declare
     * ['method' => 'get'] to accept GET — and is then extra-careful about
     * what it does.
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

        $allowed = $details['method'] ?? 'post';

        $isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

        if ($allowed === 'post') {
            // Not $_REQUEST: it would accept the nonce from a GET query
            // string on a POST-only action, and from cookies as well.
            $supplied = $_POST['_wpnonce'] ?? '';
            $supplied = is_string($supplied) ? $supplied : '';

            if (!$isPost || $supplied === '' || !wp_verify_nonce($supplied, $nonce)) {
                wp_nonce_ays($nonce);
            }

            return;
        }

        $supplied = $_GET['_wpnonce'] ?? '';
        $supplied = is_string($supplied) ? $supplied : '';

        if ($supplied === '' || !wp_verify_nonce($supplied, $nonce)) {
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


        $session = &session();

        if($this->entry != null) {
            if(empty($session['errors']) || empty($session['form_data'])) {
                $form->fill($this->entry);
            }
            $idField = '<input type="hidden" name="id" value="'. esc_attr($this->entry->getKey()) .'"></input>';
        }else{
            $idField = '';
        }

        if(!empty($session['errors'])){
            if (!empty($session['form_data'])) {
                $form->fill($session['form_data']);
                unset($session['form_data']);
            }
        }

        $adminPost= $this->adminPost;
        $action = $this->action;

        // $action is the full admin_post hook; the nonce must be keyed on the
        // declared action key, which the hook map holds even for snake_case
        // names that a naive last-segment split would mangle.
        $actionName = $action === ''
            ? 'save'
            : (self::resolveActionKey($action) ?? substr($action, strrpos($action, $this->separator) + 1));

        $nonceField = wp_nonce_field($this->nonceAction($actionName), '_wpnonce', true, false);

        add_action('wp_enqueue_scripts', [$this, 'enqueue']);

        require_once $this->formView;

    }

    protected function setEntry(Model $model): static
    {
        $this->entry = $model;
        return $this;
    }

    /**
     * Returns to the previous admin page.
     *
     * wp_safe_redirect keeps the target on this host: the raw HTTP_REFERER is
     * client-supplied, so echoing it into a Location header is both an open
     * redirect and a header-injection vector.
     */
    public function goBack(): void
    {
        wp_safe_redirect(wp_get_referer() ?: admin_url());
        exit;
    }

    /**
     * Validation rules for an action, keyed by field. Declaring them here is
     * enough — submitted() runs them, stores the messages and returns the
     * user to the form with their input intact.
     *
     * @return array<string,string>
     */
    public function rules(string $action = 'save'): array
    {
        return [];
    }

    /**
     * Validated POST data for the current action, or never returns: on
     * failure it stores the errors and redirects back to the form.
     *
     * Replaces the Validator + $_SESSION + goBack() block that every
     * submit() handler would otherwise repeat.
     */
    protected function submitted(?string $action = null): array
    {
        $action = $action ?? $this->getCurrentAction() ?? 'save';

        $data = wp_unslash($_POST);
        $rules = $this->rules($action);

        if (!$rules) {
            return $data;
        }

        $validation = (new Validator())->validate($data, $rules);

        if ($validation->fails()) {
            $this->flashErrors($validation->errors()->all('<li>:message</li>'), $data);
            $this->goBack();
        }

        return $validation->getValidatedData();
    }

    /** Stores messages and the submitted values for the form to render. */
    protected function flashErrors(array $errors, array $data = []): void
    {
        $session = &session();

        $session['errors'] = $errors;
        $session['form_data'] = $data;
    }

    protected function flashSuccess(string $message): void
    {
        $session = &session();

        $session['success'] = $message;
    }

    /**
     * The declared action key serving this request. Resolved through the
     * hook map when the guard has run, so snake_case keys survive; the last
     * segment is only a fallback for requests that bypassed it.
     */
    protected function getCurrentAction()
    {
        if ($this->currentActionKey !== null) {
            return $this->currentActionKey;
        }

        $action = $_POST['action'] ?? null;

        if ($action && is_string($action)) {
            $action = self::resolveActionKey($action) ??
                explode($this->separator, $action)[count(explode($this->separator, $action)) - 1];
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

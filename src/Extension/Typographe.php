<?php
/**
 * @copyright Copyright (C) 2026 Yann Gomiero
 * @license GNU General Public License version 3 or later; see LICENSE
 */
namespace Yann\Plugin\System\Typographe\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Event\Model\BeforeSaveEvent;
use Joomla\CMS\Event\Model\PrepareFormEvent;
use Joomla\CMS\Event\Plugin\AjaxEvent;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use Yann\Plugin\System\Typographe\Service\Typography;

final class Typographe extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;
    protected $allowLegacyListeners = false;

    public static function getSubscribedEvents(): array
    {
        return ['onContentBeforeSave' => 'beforeSave', 'onExtensionBeforeSave' => 'beforeSave', 'onContentPrepareForm' => 'prepareForm',
            'onAjaxTypographe' => 'check'];
    }

    public function beforeSave(BeforeSaveEvent $event): void
    {
        $app = $this->getApplication();
        if ((!$app->isClient('administrator') && !$app->isClient('site'))
            || $app->getInput()->getMethod() !== 'POST'
            || !$this->params->get('automatic', 1)) { return; }
        $item = $event->getItem();
        $data = $event->getData();
        $context = $event->getContext();
        $fields = [];
        if (in_array($context, ['com_content.article','com_content.form'], true) && array_key_exists('articletext', $data)) {
            $fields = ['introtext', 'fulltext'];
        } elseif ($context === 'com_categories.category' && array_key_exists('description', $data) && $this->params->get('categories', 1)) {
            $fields = ['description'];
        } elseif ($context === 'com_modules.module' && ($item->module ?? '') === 'mod_custom'
            && array_key_exists('content', $data) && $this->params->get('modules', 1)) {
            $fields = ['content'];
        }
        if (!$fields) { return; }
        // A manual selection overrides automation for this exact submitted revision.
        // Joomla's controller still owns authentication, CSRF, ACL and HTML filtering.
        if ($app->isClient('administrator')) {
            $review = json_decode($app->getInput()->post->get('typographe_reviewed', '', 'raw'), true);
            $posted = $app->getInput()->post->get('jform', [], 'array');
            $bodyKey = $fields[0] === 'introtext' ? 'articletext' : $fields[0];
            $lineEndings = static fn ($text) => is_string($text) ? str_replace(["\r\n", "\r"], "\n", $text) : null;
            if (is_array($review) && isset($review['text'], $review['title'], $review['language'])
                && in_array($lineEndings($posted[$bodyKey] ?? null), [$lineEndings($review['text']), $lineEndings($review['editorText'] ?? $review['text'])], true)
                && $review['title'] === ($posted['title'] ?? null)
                && ($review['language'] ?: '*') === (($posted['language'] ?? '') ?: '*')) { return; }
        }
        if ($this->params->get('titles', 1)) { $fields[] = 'title'; }
        $service = new Typography($this->params->toArray());
        $changes = [];
        try {
            foreach ($fields as $field) {
                if (isset($item->$field)) {
                    $changes[$field] = $field === 'title' ? $service->fixPlain((string) $item->$field, $item->language ?? '*')
                        : $service->fix((string) $item->$field, $item->language ?? '*');
                }
            }
        } catch (\Throwable $error) {
            $this->loadLanguage();
            $this->getApplication()->enqueueMessage(Text::_('PLG_SYSTEM_TYPOGRAPHE_ERROR'), 'error');
            $event->addResult(false);
            return;
        }
        foreach ($changes as $field => $value) { $item->$field = $value; }
        $event->addResult(true);
    }

    public function prepareForm(PrepareFormEvent $event): void
    {
        $app = $this->getApplication();
        if (!$app->isClient('administrator')) { return; }
        $form = $event->getForm();
        $name = $form->getName();
        $kind = ''; $field = '';
        if ($name === 'com_content.article') { $kind = 'article'; $field = 'articletext'; }
        elseif (str_starts_with($name, 'com_categories.category') && $this->params->get('categories', 1)) { $kind = 'category'; $field = 'description'; }
        elseif (str_starts_with($name, 'com_modules.module') && $this->params->get('modules', 1)) {
            $data = (array) $event->getData();
            if (($data['module'] ?? $form->getValue('module')) === 'mod_custom') { $kind = 'module'; $field = 'content'; }
        }
        if (!$kind) { return; }
        $this->loadLanguage();
        $document = $app->getDocument();
        foreach (['CHECK','APPLY','UNDO','CLOSE','BEFORE','AFTER','CLEAN','CHANGED','ERROR','STALE','APPLIED','UNDONE','BUSY','TITLE','BODY','HELP','RENDERED','SOURCE','HIGHLIGHT','ACCEPT','REJECT','ACCEPT_ALL','REJECT_ALL','SELECTED','CORRECTIONS','CHOICE_HELP','CLICK_HELP'] as $key) {
            Text::script('PLG_SYSTEM_TYPOGRAPHE_' . $key);
        }
        $document->addScriptOptions('typographe', [
            'url' => Uri::base() . 'index.php?option=com_ajax&plugin=typographe&group=system&format=json',
            'token' => Session::getFormToken(), 'title' => (bool) $this->params->get('titles', 1),
            'kind' => $kind, 'field' => 'jform_' . $field,
        ]);
        $document->getWebAssetManager()
            ->registerAndUseScript('plg_system_typographe.compare', Uri::root() . 'media/plg_system_typographe/compare.js', ['version' => '2.0.8'], ['defer' => true])
            ->registerAndUseScript('plg_system_typographe.editor', Uri::root() . 'media/plg_system_typographe/editor.js', ['version' => '2.0.8'], ['defer' => true], ['core', 'plg_system_typographe.compare'])
            ->registerAndUseStyle('plg_system_typographe.editor', Uri::root() . 'media/plg_system_typographe/editor.css', ['version' => '2.0.8']);
    }

    public function check(AjaxEvent $event): void
    {
        $app = $this->getApplication();
        $this->loadLanguage();
        if (!$app->isClient('administrator') || $app->getIdentity()->guest
            || $app->getInput()->getMethod() !== 'POST' || !Session::checkToken('post')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        $input = $app->getInput()->post;
        $id = $input->getInt('id');
        $user = $app->getIdentity();
        $kind = $input->getCmd('kind', 'article');
        if (!in_array($kind, ['article','category','module'], true)
            || ($kind === 'category' && !$this->params->get('categories', 1))
            || ($kind === 'module' && !$this->params->get('modules', 1))) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        if ($kind === 'category') {
            $db = $this->getDatabase();
            $db->setQuery($db->getQuery(true)->select(['id','extension'])->from('#__categories')->where('id = ' . $id));
            $category = $db->loadObject();
            $extension = $category->extension ?? $input->getCmd('extension', 'com_content');
            $allowed = $id ? $category && $user->authorise('core.edit', $extension . '.category.' . $id)
                : $user->authorise('core.create', $extension);
        } elseif ($kind === 'module') {
            $db = $this->getDatabase();
            $db->setQuery($db->getQuery(true)->select(['id','module'])->from('#__modules')->where('id = ' . $id));
            $module = $db->loadObject();
            $allowed = $id ? $module && $module->module === 'mod_custom' && $user->authorise('core.edit', 'com_modules.module.' . $id)
                : $user->authorise('core.create', 'com_modules');
        } elseif ($id > 0) {
            $db = $this->getDatabase();
            $db->setQuery($db->getQuery(true)->select(['id','created_by'])->from('#__content')->where('id = ' . $id));
            $article = $db->loadObject();
            $allowed = $article && ($user->authorise('core.edit', 'com_content.article.' . $id)
                || ((int) $article->created_by === (int) $user->id && $user->authorise('core.edit.own', 'com_content.article.' . $id)));
        } else {
            $category = $input->getInt('catid');
            $allowed = $user->authorise('core.create', $category ? 'com_content.category.' . $category : 'com_content');
        }
        if (!$allowed) { throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403); }
        $text = $input->get('text', '', 'raw');
        $title = $input->get('title', '', 'raw');
        if (!is_string($text) || !is_string($title) || strlen($text) > 1000000 || strlen($title) > 10000
            || !mb_check_encoding($text . $title, 'UTF-8')) {
            throw new \InvalidArgumentException(Text::_('PLG_SYSTEM_TYPOGRAPHE_ERROR'), 400);
        }
        $service = new Typography($this->params->toArray());
        $language = $input->getString('language', '*');
        $fixed = $service->fix($text, $language);
        $fixedTitle = $this->params->get('titles', 1) ? $service->fixPlain($title, $language) : $title;
        $event->addResult(['text' => $fixed, 'title' => $fixedTitle, 'changed' => $fixed !== $text || $fixedTitle !== $title]);
    }

}



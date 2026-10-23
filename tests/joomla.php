<?php
// php tests/joomla.php D:/path/to/joomla
define('_JEXEC',1);
define('JPATH_BASE',rtrim($argv[1] ?? '', '/\\'));
require JPATH_BASE.'/includes/defines.php';
require JPATH_LIBRARIES.'/bootstrap.php';
require dirname(__DIR__).'/src/Extension/Typographe.php';
spl_autoload_register(static function($class){
    if ($class === 'Yann\\Plugin\\System\\Typographe\\Service\\Typography') require dirname(__DIR__).'/src/Service/Typography.php';
});
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Event\Model\BeforeSaveEvent;
use Joomla\Input\Input;
use Yann\Plugin\System\Typographe\Extension\Typographe;
class TestApplication extends CMSApplication {
    protected function doExecute() {}
    public string $testClient='site';
    private Input $testInput;
    public function __construct(){ $this->testInput=new Input; }
    public function getName(){return $this->testClient;}
    public function getInput(): Input{return $this->testInput;}
}
$app=new TestApplication;
$plugin=new Typographe(['name'=>'typographe','type'=>'system','params'=>[]]);
$plugin->setApplication($app);
$count=0;
function ok(bool $pass,string $name):void{global $count;if(!$pass)throw new RuntimeException($name);echo 'PASS '.$name."\n";$count++;}
function saveEvent(string $context,object $item,array $data=[]):BeforeSaveEvent{return new BeforeSaveEvent('onContentBeforeSave',['context'=>$context,'subject'=>$item,'isNew'=>false,'data'=>$data]);}
ok(!isset(Typographe::getSubscribedEvents()['onContentPrepare']),'No display-time event');
$item=(object)['title'=>'Test...','introtext'=>'<p>Texte...</p>','language'=>'*'];
$app->getInput()->server->set('REQUEST_METHOD','GET');
$plugin->beforeSave(saveEvent('com_content.article',$item,['articletext'=>'x']));
ok($item->introtext==='<p>Texte...</p>','GET never changes text');
ok(!class_exists('JoliTypo\\Fixer',false) && !class_exists('Yann\\Plugin\\System\\Typographe\\Service\\Typography',false),'Engine not loaded for display');
$app->getInput()->server->set('REQUEST_METHOD','POST');
$app->testClient='api';
$plugin->beforeSave(saveEvent('com_content.article',$item,['articletext'=>'x']));
ok($item->introtext==='<p>Texte...</p>','API ignored');
$app->testClient='administrator';
$plugin->beforeSave(saveEvent('com_content.article',$item,[]));
ok($item->introtext==='<p>Texte...</p>','Non-editor save ignored');
$plugin->params->set('automatic',0);
$plugin->beforeSave(saveEvent('com_content.article',$item,['articletext'=>'x']));
ok($item->introtext==='<p>Texte...</p>','Manual mode preserves save');
$plugin->params->set('automatic',1);
$plugin->beforeSave(saveEvent('com_content.article',$item,['articletext'=>'x']));
ok($item->introtext==='<p>Texte…</p>','Article correction');
$module=(object)['module'=>'mod_custom','title'=>'Module...','content'=>'<p>Module...</p>'];
$plugin->beforeSave(saveEvent('com_modules.module',$module,['content'=>'x']));
ok($module->content==='<p>Module…</p>','Custom module correction');
$other=(object)['module'=>'mod_menu','content'=>'<p>Menu...</p>'];
$plugin->beforeSave(saveEvent('com_modules.module',$other,['content'=>'x']));
ok($other->content==='<p>Menu...</p>','Other modules excluded');
$category=(object)['title'=>'Category...','description'=>'<p>Description...</p>'];
$plugin->beforeSave(saveEvent('com_categories.category',$category,['description'=>'x']));
ok($category->description==='<p>Description…</p>','Category description correction');
$plugin->params->set('titles',0);
$item->title='Title...';
$plugin->beforeSave(saveEvent('com_content.article',$item,['articletext'=>'x']));
ok($item->title==='Title...','Title option respected');
$app->testClient='site';$item->introtext='<p>Front editor...</p>';
$plugin->beforeSave(saveEvent('com_content.form',$item,['articletext'=>'x']));
ok($item->introtext==='<p>Front editor…</p>','Frontend editing save supported');
echo "$count Joomla integration tests passed\n";

$app->testClient='administrator';
$item=(object)['title'=>'Review','introtext'=>'<p>Keep... Change…</p>','language'=>'*'];
$posted=['title'=>'Review','articletext'=>$item->introtext,'language'=>'*'];
$app->getInput()->post->set('jform',$posted);
$app->getInput()->post->set('typographe_reviewed',json_encode(['title'=>'Review','text'=>$item->introtext,'language'=>'*']));
$plugin->beforeSave(saveEvent('com_content.article',$item,$posted));
ok($item->introtext==='<p>Keep... Change…</p>','Rejected change survives automatic save');
$posted['articletext']='<p>New...</p>';$item->introtext=$posted['articletext'];
$app->getInput()->post->set('jform',$posted);
$plugin->beforeSave(saveEvent('com_content.article',$item,$posted));
ok($item->introtext==='<p>New…</p>','Stale selection does not bypass automatic save');
echo "$count total Joomla integration tests passed\n";

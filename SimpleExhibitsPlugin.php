<?php
/**
 * Simple Exhibits
 *
 * Based on Simple Pages:
 * @copyright Copyright 2008-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

require_once dirname(__FILE__) . '/helpers/SimpleExhibitFunctions.php';

define('CKC_SEXHIBITS_COVERS_DIR', realpath(FILES_DIR) . '/simple_exhibits_covers'); //20201109 CKC: directory for storing cover images
define('CKC_SEXHIBITS_COVERS_URI', WEB_FILES . '/simple_exhibits_covers'); //20201111 CKC: for convenience
/**
 * Simple Exhibits plugin.
 */
class SimpleExhibitsPlugin extends Omeka_Plugin_AbstractPlugin
{
    /**
     * @var array Hooks for the plugin.
     */
    protected $_hooks = array('install', 'uninstall', 'upgrade', 'initialize',
        'define_acl', 'define_routes', 'html_purifier_form_submission');

    /**
     * @var array Filters for the plugin.
     */
    protected $_filters = array('admin_navigation_main',
        'public_navigation_main', 'search_record_types', 'page_caching_whitelist',
        'page_caching_blacklist_for_record',
        'api_resources', 'api_import_omeka_adapters');

    /**
     * Install the plugin.
     */
    public function hookInstall()
    {
        // Create the table.
        $db = $this->_db;
        $sql = "
        CREATE TABLE IF NOT EXISTS `$db->SimpleExhibitsPage` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `modified_by_user_id` int(10) unsigned NOT NULL,
          `created_by_user_id` int(10) unsigned NOT NULL,
          `is_published` tinyint(1) NOT NULL,
          `is_featured` tinyint(1) NOT NULL,
          `title` tinytext COLLATE utf8_unicode_ci NOT NULL,
          `slug` tinytext COLLATE utf8_unicode_ci NOT NULL,
          `text` mediumtext COLLATE utf8_unicode_ci,
          `content` mediumtext COLLATE utf8_unicode_CI,  
          `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `inserted` timestamp NOT NULL DEFAULT '2000-01-01 00:00:00',
          `order` int(10) unsigned NOT NULL,
          `parent_id` int(10) unsigned NOT NULL,
          `template` tinytext COLLATE utf8_unicode_ci NOT NULL,
          `use_tiny_mce_text` tinyint(1) NOT NULL,
          `use_tiny_mce_content` tinyint(1) NOT NULL,
          `ckc_cover_image` TEXT,
          PRIMARY KEY (`id`),
          KEY `is_published` (`is_published`),
          KEY `is_featured` (`is_featured`),
          KEY `inserted` (`inserted`),
          KEY `updated` (`updated`),
          KEY `created_by_user_id` (`created_by_user_id`),
          KEY `modified_by_user_id` (`modified_by_user_id`),
          KEY `order` (`order`),
          KEY `parent_id` (`parent_id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $db->query($sql);
        
        //        $made = @mkdir( CKC_SEXHIBITS_COVERS_DIR, 0770, true );
        $made = @mkdir( CKC_SEXHIBITS_COVERS_DIR, 0771, true );

        if ( $made !== true || is_readable( CKC_SEXHIBITS_COVERS_DIR ) === false ) {
            throw new Omeka_Storage_Exception('Error creating directory: ' . CKC_SEXHIBITS_COVERS_DIR);
        }



        // Save an example page.
        $page = new SimpleExhibitsPage;
        $page->modified_by_user_id = current_user()->id;
        $page->created_by_user_id = current_user()->id;
        $page->is_published = 1;
        $page->parent_id = 0;
        $page->title = 'Example simple exhibit';
        $page->slug = 'example';
        $page->text = '<p>This the header of an example exhibit. Feel free to replace this content, or delete the exhibit and start from scratch.</p>';
        $page->content = '<p>This is the content field of an example exhibit.<p>';
        $page->save();
    }

    /**
     * Uninstall the plugin.
     */
    public function hookUninstall()
    {        
        // Drop the table.
        $db = $this->_db;
        $sql = "DROP TABLE IF EXISTS `$db->SimpleExhibitsPage`";
        $db->query($sql);
    }

    /**
     * Upgrade the plugin.
     *
     * @param array $args contains: 'old_version' and 'new_version'
     */
    public function hookUpgrade($args)
    {
        $oldVersion = $args['old_version'];
        $newVersion = $args['new_version'];
        $db = $this->_db;

        // MySQL 5.7+ fix; must do first or else MySQL complains about any other ALTER
        if ($oldVersion < '3.0.7') {
            $db->query("ALTER TABLE `$db->SimpleExhibitsPage` ALTER `inserted` SET DEFAULT '2000-01-01 00:00:00'");
        }

        if ($oldVersion < '1.0') {
            $sql = "ALTER TABLE `$db->SimpleExhibitsPage` ADD INDEX ( `is_published` )";
            $db->query($sql);    
            
            $sql = "ALTER TABLE `$db->SimpleExhibitsPage` ADD INDEX ( `inserted` ) ";
            $db->query($sql);    
            
            $sql = "ALTER TABLE `$db->SimpleExhibitsPage` ADD INDEX ( `updated` ) ";
            $db->query($sql);    
            
            $sql = "ALTER TABLE `$db->SimpleExhibitsPage` ADD INDEX ( `add_to_public_nav` ) ";
            $db->query($sql);    
            
            $sql = "ALTER TABLE `$db->SimpleExhibitsPage` ADD INDEX ( `created_by_user_id` ) ";
            $db->query($sql);    
            
            $sql = "ALTER TABLE `$db->SimpleExhibitsPage` ADD INDEX ( `modified_by_user_id` ) ";
            $db->query($sql);    
            
            $sql = "ALTER TABLE `$db->SimpleExhibitsPage` ADD `order` INT UNSIGNED NOT NULL ";
            $db->query($sql);
            
            $sql = "ALTER TABLE `$db->SimpleExhibitsPage` ADD INDEX ( `order` ) ";
            $db->query($sql);
            
            $sql = "ALTER TABLE `$db->SimpleExhibitsPage` ADD `parent_id` INT UNSIGNED NOT NULL ";
            $db->query($sql);
            
            $sql = "ALTER TABLE `$db->SimpleExhibitsPage` ADD INDEX ( `parent_id` ) ";
            $db->query($sql);
            
            $sql = "ALTER TABLE `$db->SimpleExhibitsPage` ADD `template` TINYTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ";
            $db->query($sql);
        }

        if ($oldVersion < '1.3') {
            $sql = "ALTER TABLE `$db->SimpleExhibitsPage` ADD `use_tiny_mce` TINYINT(1) NOT NULL";
            $db->query($sql);
        }

        if ($oldVersion < '2.0') {
            $db->query("ALTER TABLE `$db->SimpleExhibitsPage` DROP `add_to_public_nav`");
            delete_option('simple_exhibits_home_page_id');
        }

        if ($oldVersion < '3.0.2') {
            $db->query("ALTER TABLE `$db->SimpleExhibitsPage` MODIFY `text` MEDIUMTEXT COLLATE utf8_unicode_ci");
        }

        if ($oldVersion < '3.1.1') {
            delete_option('simple_exhibits_filter_page_content');
        }
        if ( $oldVersion < '9999.20201109' ) { //20201109 CKC
            $db->query("ALTER TABLE `$db->SimplePagesPage` ADD COLUMN `ckc_cover_image` TEXT COLLATE utf8_unicode_ci");

            $made = @mkdir( CKC_SEXHIBITS_COVERS_DIR, 0770, true );
            if ( $made !== true || is_readable( CKC_SEXHIBITS_COVERS_DIR ) === false ) {
                throw new Omeka_Storage_Exception('Error creating directory: ' . CKC_SEXHIBITS_COVERS_DIR);
            }
        }
    }

    /**
     * Add the translations.
     */
    public function hookInitialize()
    {
        add_translation_source(dirname(__FILE__) . '/languages');
    }

    /**
     * Define the ACL.
     * 
     * @param Omeka_Acl
     */
    public function hookDefineAcl($args)
    {
        $acl = $args['acl'];
        
        $indexResource = new Zend_Acl_Resource('SimpleExhibits_Index');
        $pageResource = new Zend_Acl_Resource('SimpleExhibits_Page');
        $acl->add($indexResource);
        $acl->add($pageResource);

        $acl->allow(array('super', 'admin'), array('SimpleExhibits_Index', 'SimpleExhibits_Page'));
        $acl->allow(null, 'SimpleExhibits_Page', 'show');
        $acl->deny(null, 'SimpleExhibits_Page', 'show-unpublished');
    }

    /**
     * Add the routes for accessing Simple Exhibitsby slug.
     * 
     * @param Zend_Controller_Router_Rewrite $router
     */
    public function hookDefineRoutes($args)
    {
        // Don't add these routes on the admin side to avoid conflicts.
        if (is_admin_theme()) {
            return;
        }

        $router = $args['router'];

        // Add custom routes based on the page slug.
        $pages = get_db()->getTable('SimpleExhibitsPage')->findAll();
        foreach ($pages as $page) {
            $router->addRoute(
                'simple_exhibits_show_page_' . $page->id, 
                new Zend_Controller_Router_Route(
                    $page->slug, 
                    array(
                        'module'       => 'simple-exhibits', 
                        'controller'   => 'page', 
                        'action'       => 'show', 
                        'id'           => $page->id
                    )
                )
            );
        }
    }

    /**
     * Filter the 'text' field of the simple-exhibits form
     * 
     * @param array $args Hook args, contains:
     *  'request': Zend_Controller_Request_Http
     *  'purifier': HTMLPurifier
     */
    public function hookHtmlPurifierFormSubmission($args)
    {
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $purifier = $args['purifier'];

        // If we aren't editing or adding a page in SimpleExhibits, don't do anything.
        if ($request->getModuleName() != 'simple-exhibits' or !in_array($request->getActionName(), array('edit', 'add'))) {
            return;
        }
        
        $post = $request->getPost();
        $post['text'] = $purifier->purify($post['text']); 
        $request->setPost($post);
    }

    /**
     * Add the Simple Exhibitslink to the admin main navigation.
     * 
     * @param array Navigation array.
     * @return array Filtered navigation array.
     */
    public function filterAdminNavigationMain($nav)
    {
        $nav[] = array(
            'label' => __('Simple Exhibits'),
            'uri' => url('simple-exhibits'),
            'resource' => 'SimpleExhibits_Index',
            'privilege' => 'browse'
        );
        return $nav;
    }

    /**
     * Add the pages to the public main navigation options.
     * 
     * @param array Navigation array.
     * @return array Filtered navigation array.
     */
    public function filterPublicNavigationMain($nav)
    {
        $navLinks = simple_exhibits_get_links_for_children_pages(0, 'order', true);
        $nav = array_merge($nav, $navLinks);
        return $nav;
    }

    /**
     * Add SimpleExhibitsPage as a searchable type.
     */
    public function filterSearchRecordTypes($recordTypes)
    {
        $recordTypes['SimpleExhibitsPage'] = __('Simple Exhibit');
        return $recordTypes;
    }

    /**
     * Specify the default list of urls to whitelist
     * 
     * @param $whitelist array An associative array urls to whitelist, 
     * where the key is a regular expression of relative urls to whitelist 
     * and the value is an array of Zend_Cache front end settings
     * @return array The whitelist
     */
    public function filterPageCachingWhitelist($whitelist)
    {
        // Add custom routes based on the page slug.
        $pages = get_db()->getTable('SimpleExhibitsPage')->findAll();
        foreach($pages as $page) {
            $whitelist['/' . trim($page->slug, '/')] = array('cache'=>true);
        }
            
        return $whitelist;
    }

    /**
     * Add pages to the blacklist
     * 
     * @param $blacklist array An associative array urls to blacklist, 
     * where the key is a regular expression of relative urls to blacklist 
     * and the value is an array of Zend_Cache front end settings
     * @param $record
     * @param $args Filter arguments. contains:
     * - record: the record
     * - action: the action
     * @return array The blacklist
     */
    public function filterPageCachingBlacklistForRecord($blacklist, $args)
    {
        $record = $args['record'];
        $action = $args['action'];

        if ($record instanceof SimpleExhibitsPage) {
            $page = $record;
            if ($action == 'update' || $action == 'delete') {
                $blacklist['/' . trim($page->slug, '/')] = array('cache'=>false);
            }
        }
            
        return $blacklist;
    }
    public function filterApiResources($apiResources)
    {
	$apiResources['simple_exhibits'] = array(
		'record_type' => 'SimpleExhibitsPage',
		'actions'   => array('get','index'),
        'index_params' => array(
            'advanced',
            'search',
            'featured',
            'slug',
        )
	);	
       return $apiResources;
    }
    
    public function filterApiImportOmekaAdapters($adapters, $args)
    {
        $SimpleExhibitsAdapter = new ApiImport_ResponseAdapter_Omeka_GenericAdapter(null, $args['endpointUri'], 'SimpleExhibitsPage');
        $SimpleExhibitsAdapter->setService($args['omeka_service']);
        $SimpleExhibitsAdapter->setUserProperties(array('modified_by_user', 'created_by_user'));
        $adapters['simple_exhibits'] = $SimpleExhibitsAdapter;
        return $adapters;
    }
}

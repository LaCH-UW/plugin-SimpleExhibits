<?php
/**
 * @version $Id$
 * @copyright Center for History and New Media, 2010
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 **/
class SimplePages_Test_AppTestCase extends Omeka_Test_AppTestCase
{
    const PLUGIN_NAME = 'SimplePages';
    
    public function setUp()
    {
        parent::setUp();
        
        // Authenticate and set the current user 
        $this->user = $this->db->getTable('User')->find(1);
        $this->_authenticateUser($this->user);
        Omeka_Context::getInstance()->setCurrentUser($this->user);
                
        // Add the plugin hooks and filters (including the install hook)
        $pluginBroker = get_plugin_broker();
        $this->_addPluginHooksAndFilters($pluginBroker, self::PLUGIN_NAME);
        
        // Install the plugin
        $plugin = $this->_installPlugin(self::PLUGIN_NAME);
        $this->assertTrue($plugin->isInstalled());
        
        // Initialize the core resource plugin hooks and filters (like the initialize hook)
        $this->_initializeCoreResourcePluginHooksAndFilters($pluginBroker, self::PLUGIN_NAME);
    }
        
    public function _addPluginHooksAndFilters($pluginBroker, $pluginName)
    {   
        // Set the current plugin so the add_plugin_hook function works
        $pluginBroker->setCurrentPluginDirName($pluginName);
        
        // Add plugin hooks.
        add_plugin_hook('install', 'simple_pages_install');
        add_plugin_hook('uninstall', 'simple_pages_uninstall');
        add_plugin_hook('upgrade', 'simple_pages_upgrade');
        add_plugin_hook('define_acl', 'simple_pages_define_acl');
        add_plugin_hook('config_form', 'simple_pages_config_form');
        add_plugin_hook('config', 'simple_pages_config');
        add_plugin_hook('initialize', 'simple_pages_initialize');

        // Custom plugin hooks from other plugins.
        add_plugin_hook('html_purifier_form_submission', 'simple_pages_filter_html');

        // Add filters.
        add_filter('admin_navigation_main', 'simple_pages_admin_navigation_main');
        add_filter('public_navigation_main', 'simple_pages_public_navigation_main');

        add_filter('page_caching_whitelist', 'simple_pages_page_caching_whitelist');
        add_filter('page_caching_blacklist_for_record', 'simple_pages_page_caching_blacklist_for_record');        
    }
    
    public function assertPreConditions()
    {
        $pages = $this->db->getTable('SimplePagesPage')->findAll();
        $this->assertEquals(1, count($pages), 'There should be one page.');
        
        $aboutPage = $pages[0];
        $this->assertEquals('About', $aboutPage->title, 'The about page has the wrong title.');
        $this->assertEquals('about', $aboutPage->slug, 'The about page has the wrong slug.');
    }
}
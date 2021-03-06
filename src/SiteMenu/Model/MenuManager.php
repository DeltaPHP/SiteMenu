<?php
/**
 * User: Vasiliy Shvakin (orbisnull) zen4dev@gmail.com
 */

namespace SiteMenu\Model;


use DeltaCore\ModuleManager;
use DeltaCore\Parts\MagicSetGetManagers;
use DeltaCore\Prototype\MagicMethodInterface;
use DeltaRouter\Router;
use DeltaUtils\ArrayUtils;
use DeltaUtils\FileSystem;

/**
 * Class MenuManager
 * @package SiteMenu\Model
 * @method setAclManager(\Acl\Model\AclManager $manager)
 * @method \Acl\Model\AclManager getAclManager()
 */
class MenuManager implements \ArrayAccess, MagicMethodInterface
{
    use MagicSetGetManagers;

    /** @var  ModuleManager */
    protected $moduleManager;

    /** @var array Menu[] */
    protected $menuStore;

    protected $configDir;

    /** @var  Router */
    protected $router;

    /**
     * @return ModuleManager
     */
    public function getModuleManager()
    {
        return $this->moduleManager;
    }

    /**
     * @param ModuleManager $moduleManager
     */
    public function setModuleManager($moduleManager)
    {
        $this->moduleManager = $moduleManager;
    }

    /**
     * @return Router
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * @param Router $router
     */
    public function setRouter($router)
    {
        $this->router = $router;
    }

    /**
     * @return mixed
     */
    public function getConfigDir()
    {
        if (is_null($this->configDir)) {
            $this->configDir =   ROOT_DIR . "/config/";
        }
        return $this->configDir;
    }

    /**
     * @param mixed $configDir
     */
    public function setConfigDir($configDir)
    {
        $this->configDir = $configDir;
    }

    /**
     * @param string $menuName
     * @return Menu
     */
    public function getMenu($menuName)
    {
        if (is_null($this->menuStore)) {
            $menuData = $this->readMenu();
            $this->loadMenu($menuData);
        }
        $menu = isset($this->menuStore[$menuName]) ? $this->menuStore[$menuName] : null;
        return $menu;
    }

    protected function readMenuRaw($path, $default = [])
    {
        $menuData = FileSystem::getPhpConfig($path, null);
        if (empty($menuData)) {
            return $default;
        }
        $assocMenu = [];
        foreach($menuData as $key=>$menu) {
            foreach ($menu as $item) {
                if (isset($item["link"])) {
                    $assocMenu[$key][$item["link"]] = $item;
                }
            }
        }
        return $assocMenu;
    }

    public function readMenu()
    {
        $configDir = $this->getConfigDir();
        $globalMenu = $this->readMenuRaw($configDir . "global.menu.php", []);
        $localMenu = $this->readMenuRaw($configDir . "local.menu.php", []);
        $moduleManager = $this->getModuleManager();
        $modules = $moduleManager->getModulesList();
        $menuConfig = [];
        foreach($modules as $moduleName) {
            $modulePath = $moduleManager->getModulePath($moduleName);
            $moduleConfig = $this->readMenuRaw($modulePath . "/config/menu.php", null);
            if ($moduleConfig) {
                $menuConfig = array_merge_recursive($menuConfig, $moduleConfig);
            }
        }
        $menuConfig = ArrayUtils::mergeRecursive($menuConfig, $globalMenu, $localMenu);
        return $menuConfig;
    }

    public function loadMenu($menuConfig)
    {
        foreach($menuConfig as $name=>$itemsData) {
            $menu = new Menu($name, $this->getRouter());
            $menu->setAclManager($this->getAclManager());
            $i=0;
            foreach($itemsData as $key=>$itemData) {
                $i++;
                if (!isset($itemData["order"])) {
                    $itemData["order"] = $i;
                }
                $item = new Item($itemData);
                $menu->addItem($item);
            }
            $this->menuStore[$name] = $menu;
        }
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return !is_null($this->getMenu($offset));
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        return $this->getMenu($offset);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        return;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($offset)
    {
        return;
    }


}
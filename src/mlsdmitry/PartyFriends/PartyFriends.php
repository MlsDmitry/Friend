<?php


namespace mlsdmitry\PartyFriends;


use mlsdmitry\LangAPI\Lang;
use mlsdmitry\PartyFriends\friends\FManager;
use mlsdmitry\PartyFriends\party\PManager;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use mlsdmitry\PartyFriends\friends\FCommands;
use mlsdmitry\PartyFriends\party\PCommands;
use pocketmine\Server;
use pocketmine\utils\Config;

class PartyFriends extends PluginBase
{
    /** @var PartyFriends $instance */
    private static $instance;
    /** @var Config $friends_db */
    public static $friends_db;
    /** @var Config $associations */
    public static $associations;
    /** @var array $cached_players */
    public static $cached_players = [];

    /**
     * @param string $nick_name
     * @return Player|null
     */
    public static function getCachedPlayer($nick_name)
    {
        $name = Utils::name($nick_name);
        if (isset(self::$cached_players[$name]))
            if (!self::$cached_players[$name]->isOnline()) {
                unset(self::$cached_players[$name]);
                return null;
            } else
                return self::$cached_players[$name];
        else {
            self::$cached_players[$name] = Server::getInstance()->getPlayer($name);
            return self::$cached_players[$name];
        }
    }

    public function onEnable()
    {
        $this->init_default_config();
        self::$friends_db = new Config($this->getDataFolder() . 'friends.yml', Config::YAML);
        self::$associations = new Config($this->getDataFolder() . 'associations.json', Config::JSON);
        new Lang($this);
        if (sizeof(glob($this->getDataFolder() . 'languages/' . '*.yml')) === 0) {
            $this->recurse_copy($this->getFile() . 'resources/languages/', $this->getDataFolder() . 'languages/');
        }
        $this->getServer()->getCommandMap()->registerAll('PF SYSTEM', [
            new FCommands('f', 'Manage your friends', '/f help', ['friends']),
            new PCommands('p', 'Manage your parties', '/p help', ['party'])
        ]);
        $this->getServer()->getPluginManager()->registerEvents(new PManager(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new FManager(), $this);
    }

    private function recurse_copy($src, $dst)
    {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->recurse_copy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    private function init_default_config()
    {
        $data = [
            'party_expire' => 1,
            'friend_request_expire' => 5
        ];
        foreach ($data as $key => $value) {
            if (!$this->getConfig()->exists($key))
                $this->getConfig()->set($key, $value);
        }
        $this->getConfig()->save();
    }

    public function onLoad()
    {
        self::$instance = $this;
    }

    public static function make(): PartyFriends
    {
        return self::$instance;
    }


}
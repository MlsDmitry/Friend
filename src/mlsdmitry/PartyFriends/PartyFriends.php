<?php


namespace mlsdmitry\PartyFriends;


use mlsdmitry\LangAPI\Lang;
use mlsdmitry\PartyFriends\friends\FManager;
use mlsdmitry\PartyFriends\party\PManager;
use pocketmine\plugin\PluginBase;
use mlsdmitry\PartyFriends\friends\FCommands;
use mlsdmitry\PartyFriends\party\PCommands;
use pocketmine\utils\Config;

class PartyFriends extends PluginBase
{
    /** @var PartyFriends $instance */
    private static $instance;
    /** @var Config $friends_db */
    public static $friends_db;
    /** @var Config $associations */
    public static $associations;

    public function onEnable()
    {
        $this->init_default_config();
        self::$friends_db = new Config($this->getDataFolder() . DIRECTORY_SEPARATOR . 'friends.yml', Config::YAML);
        self::$associations = new Config($this->getDataFolder() . DIRECTORY_SEPARATOR . 'associations.json', Config::JSON);
        new Lang($this);
        $this->getServer()->getCommandMap()->registerAll('PF SYSTEM', [
            new FCommands('f', 'Manage your friends', '/f help', ['friends']),
            new PCommands('p', 'Manage your parties', '/p help', ['party'])
        ]);
        $this->getServer()->getPluginManager()->registerEvents(new PManager(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new FManager(), $this);
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
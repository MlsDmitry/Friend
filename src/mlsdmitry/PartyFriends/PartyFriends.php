<?php


namespace mlsdmitry\PartyFriends;


use mlsdmitry\LangAPI\Lang;
use mlsdmitry\PartyFriends\frends\FManager;
use mlsdmitry\PartyFriends\party\PManager;
use pocketmine\plugin\PluginBase;
use mlsdmitry\PartyFriends\friends\FCommands;
use mlsdmitry\PartyFriends\party\PCommands;
use SQLite3;

class PartyFriends extends PluginBase
{
    /** @var PartyFriends $instance */
    private static $instance;
    /** @var SQLite3 $friends_db */
    private static $friends_db;

    public function onEnable()
    {
        self::$friends_db = new SQLite3($this->getDataFolder() . 'friends.sqlite3');
        new Lang($this);
        $this->getServer()->getCommandMap()->registerAll('PF SYSTEM', [
            new FCommands('f', 'Manage your friends', '/f help', ['friends']),
            new PCommands('p', 'Manage your parties', '/p help', ['party'])
        ]);
        $this->getServer()->getPluginManager()->registerEvents(new PManager(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new FManager(), $this);
    }

    private function prepareSQLite()
    {
        self::$friends_db->exec("CREATE TABLE IF NOT EXISTS friends (
            nickname TEXT,
            
        )");
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
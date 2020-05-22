<?php


namespace mlsdmitry\PartyFriends\frends;


use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

class FManager implements Listener
{
    public function onJoin(PlayerJoinEvent $event)
    {
        $p = $event->getPlayer();

    }
}
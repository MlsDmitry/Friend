<?php


namespace mlsdmitry\PartyFriends\party;


use mlsdmitry\LangAPI\Lang;
use mlsdmitry\PartyFriends\party\obj\Request;
use mlsdmitry\PartyFriends\PartyFriends;
use mlsdmitry\PartyFriends\Utils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;

class PCommands extends Command
{

//

    public function execute(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $p = PartyFriends::getCachedPlayer($sender->getName());
        var_dump('sender', $sender->getName());
        var_dump('name', $p->getName());
        if (!isset($args[0]))
            $sender->sendMessage($this->getUsage());
        switch ($args[0]) {
            case "help":
                $sender->sendMessage($this->getUsage());
                break;
            case "invite":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getUsage());
                    return;
                }
                $data = PManager::is_follower($p); // $owner_name => $party(Party obj)
                if ($data)
                    $cause = PManager::await_accept($data[1]->getOwner(), $args[1], Request::INVITE_COMMAND);
                else
                    $cause = PManager::await_accept($p, $args[1], Request::INVITE_COMMAND);
                if ($cause === PManager::SUCCESS) {
                    $sender->sendMessage(Lang::get('p-invite-success', ['nickname' => $args[1]], $p));
                } elseif ($cause === PManager::IS_OFFLINE) {
                    $sender->sendMessage(Lang::get('p-player-offline', ['nickname' => $args[1]], $p));
                }
                break;

            case "leave":
                $cause = PManager::leave($p);
                if ($cause === PManager::DONT_HAVE_PARTY) {
                    $sender->sendMessage(Lang::get('p-dont-have-party', [], $p));
                }
                break;

            case "list":
                // idk why am I using callable function, but mby somewhere in code I will need this,
                // or you will use this api(PManager::get_followers().
                // OMG I am idiot :/
                $cause = PManager::get_followers($p);
                if ($cause === PManager::DONT_HAVE_PARTY) {
                    $sender->sendMessage(Lang::get('p-dont-have-party', [], $p));
                } else {
                    $followers = [];
                    /**
                     * @var string $uuid
                     * @var Player $p
                     */
                    foreach ($cause as $uuid => $p) {
                        $followers[] = $p->getName();
                    }
                    print_r(PManager::getParties());
                    $sender->sendMessage(Lang::get('p-player-list-command', ['list' => implode(', ', $followers)], $p));
                }
                break;

            case "promote":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getUsage());
                    return;
                }
                $cause = PManager::await_accept($p, $args[1], Request::PROMOTE_COMMAND);
                if ($cause === PManager::SUCCESS) {
                    $sender->sendMessage(Lang::get('p-promote-success', ['nickname' => $args[1]], $p));
                } elseif ($cause === PManager::IS_OFFLINE) {
                    $sender->sendMessage(Lang::get('p-player-offline', ['nickname' => $args[1]], $p));
                }
                break;

            case "home":

                break;

            case "remove":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getUsage());
                    return;
                }
                break;
            case "warp":

                break;

            case "accept":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getUsage());
                    return;
                }
                $cause = PManager::accept($p, $args[1]);
                if ($cause === PManager::SUCCESS) {
                    $sender->sendMessage(Lang::get('p-accept-success', ['nickname' => $args[1]], $p));
                } elseif ($cause === PManager::IS_OFFLINE) {
                    $sender->sendMessage(Lang::get('p-player-offline', ['nickname' => $args[1]], $p));
                } elseif ($cause === PManager::NOT_FOUND) {
                    $sender->sendMessage(Lang::get('p-player-not-found', ['nickname' => $args[1]], $p));
                } elseif ($cause === PManager::PARTY_EXPIRED) {
                    $sender->sendMessage(Lang::get('p-party-expired', [], $p));
                }
                break;

            case "disband":
//                PManager::disbandParty($p);
                break;

            case "mute":

                break;
        }
    }

}
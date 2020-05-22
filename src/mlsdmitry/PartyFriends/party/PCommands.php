<?php


namespace mlsdmitry\PartyFriends\party;


use mlsdmitry\LangAPI\Lang;
use mlsdmitry\PartyFriends\party\obj\Request;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;

class PCommands extends Command
{

//

    public function execute(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $p = Server::getInstance()->getPlayer($sender->getName());
        print_r($args);
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
                $cause = PManager::await_accept($p, $args[1], Request::INVITE_COMMAND);
                var_dump('cause', $cause);
                if ($cause === true) {
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
                    $p->sendMessage(Lang::get('p-dont-have-party', [], $p));
                } else {
                    $p->sendMessage(Lang::get('p-player-list-command', ['list' => implode(' ', $cause)], $p));
                }
                break;

            case "promote":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getUsage());
                    return;
                }
                $cause = PManager::await_accept($p, $args[1], Request::PROMOTE_COMMAND);
                if ($cause === true) {
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
                if ($cause === true) {
                    $sender->sendMessage(Lang::get('accept-success', ['nickname' => $args[1]], $p));
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
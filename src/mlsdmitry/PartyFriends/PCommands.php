<?php


namespace mlsdmitry\PartyFriends;


use mlsdmitry\LangAPI\Lang;
use mlsdmitry\PartyFriends\party\Request;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;

class PCommands extends Command
{

//    protected function prepare(): void
//    {
//        //! /p help
//        $this->registerArgument(0, new RawStringArgument("help", false));
//        //! /p invite NickName
//        $this->registerArgument(0, new RawStringArgument("invite", false));
//        //! /p leave
//        $this->registerArgument(0, new RawStringArgument("leave", false));
//        //! /p list
//        $this->registerArgument(0, new RawStringArgument("list", false));
//        //! /p promote NickName
//        $this->registerArgument(0, new RawStringArgument("promote", false));
//        //! /p home
//        $this->registerArgument(0, new RawStringArgument("home", false));
//        //! /p remove NickName
//        $this->registerArgument(0, new RawStringArgument("remove", false));
//        //! /p warp
//        $this->registerArgument(0, new RawStringArgument("warp", false));
//        //! /p accept NickName
//        $this->registerArgument(0, new RawStringArgument("accept", false));
//        //! /p disband
//        $this->registerArgument(0, new RawStringArgument("disband", false));
////        $this->registerArgument(0, new RawStringArgument("disband", false));
//        //! /p mute
//        $this->registerArgument(0, new RawStringArgument("mute", false));
//        //! /p poll
//        $this->registerArgument(0, new RawStringArgument("poll", false));
//        //! /p challenge
//        $this->registerArgument(0, new RawStringArgument("challenge", false));
//
//        $this->registerArgument(1, new RawStringArgument("nick_name", false));
//
//    }

    public function execute(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $p = Server::getInstance()->getPlayer($sender->getName());
        print_r($args);
        if (!isset($args[0]))
            $sender->sendMessage($this->getUsage());
        switch ($args[0]) {
            case "help":
                $sender->sendMessage($this->getUsage());
                PManager::
                break;
            case "invite":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getUsage());
                    return;
                }
                $cause = PManager::await_accept($p, $args[1], Request::INVITE_COMMAND);
                var_dump('cause', $cause);
                if ($cause === true) {
                    $sender->sendMessage(Lang::get('invite-success', ['nickname' => $args[1]], $p));
                } elseif ($cause === PManager::IS_OFFLINE) {
                    $sender->sendMessage(Lang::get('player-offline', ['nickname' => $args[1]], $p));
                }
                break;

            case "leave":
                $cause = PManager::leave($p);
                if ($cause === PManager::DONT_HAVE_PARTY) {
                    $sender->sendMessage(Lang::get('dont-have-party', [], $p));
                }
                break;

            case "list":
                // idk why am I using callable function, but mby somewhere in code I will need this,
                // or you will use this api(PManager::get_followers().
                // OMG I am idiot :/
                $cause = PManager::get_followers($p);
                if ($cause === PManager::DONT_HAVE_PARTY) {
                    $p->sendMessage(Lang::get('dont-have-party', [], $p));
                } else {
                    $p->sendMessage(Lang::get('player-list-command', ['list' => implode(' ', $cause)], $p));
                }
                break;

            case "promote":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getUsage());
                    return;
                }
                $cause = PManager::await_accept($p, $args[1], Request::PROMOTE_COMMAND);
                if ($cause === true) {
                    $sender->sendMessage(Lang::get('promote-success', ['nickname' => $args[1]], $p));
                } elseif ($cause === PManager::IS_OFFLINE) {
                    $sender->sendMessage(Lang::get('player-offline', ['nickname' => $args[1]], $p));
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
                    $sender->sendMessage(Lang::get('player-offline', ['nickname' => $args[1]], $p));
                } elseif ($cause === PManager::NOT_FOUND) {
                    $sender->sendMessage(Lang::get('player-not-found', ['nickname' => $args[1]], $p));
                } elseif ($cause === PManager::PARTY_EXPIRED) {
                    $sender->sendMessage(Lang::get('party-expired', [], $p));
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
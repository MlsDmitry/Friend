<?php


namespace mlsdmitry\PartyFriends;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;
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
        if (isset($args[0]))
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
                PManager::try_promote_or_invite($p, $args[1], PManager::INVITE_COMMAND);
                break;

            case "leave":

                break;

            case "list":

                break;

            case "promote":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getUsage());
                    return;
                }
                PManager::try_promote_or_invite($p, $args[1], PManager::PROMOTE_COMMAND);
                break;

            case "home":

                break;

            case "remove":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getUsage());
                    return;
                }
                PManager::remove($p, $args[1]);
                break;
            case "warp":

                break;

            case "accept":
                //
                break;

            case "disband":

                break;

            case "mute":

                break;
        }
    }

}
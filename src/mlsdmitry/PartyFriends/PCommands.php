<?php


namespace mlsdmtiry\PartyFriends;


use CortexPE\Commando\args\BaseArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use pocketmine\command\CommandSender;

class PCommands extends BaseCommand
{

    protected function prepare(): void
    {
        //! /p help
        $this->registerArgument(0, new RawStringArgument("help", false));
        //! /p invite NickName
        $this->registerArgument(0, new RawStringArgument("invite", false));
        //! /p leave
        $this->registerArgument(0, new RawStringArgument("leave", false));
        //! /p list
        $this->registerArgument(0, new RawStringArgument("list", false));
        //! /p promote NickName
        $this->registerArgument(0, new RawStringArgument("promote", false));
        //! /p home
        $this->registerArgument(0, new RawStringArgument("home", false));
        //! /p remove NickName
        $this->registerArgument(0, new RawStringArgument("remove", false));
        //! /p warp
        $this->registerArgument(0, new RawStringArgument("warp", false));
        //! /p accept NickName
        $this->registerArgument(0, new RawStringArgument("accept", false));
        //! /p disband
        $this->registerArgument(0, new RawStringArgument("disband", false));
//        $this->registerArgument(0, new RawStringArgument("disband", false));
        //! /p mute
        $this->registerArgument(0, new RawStringArgument("mute", false));
        //! /p poll
        $this->registerArgument(0, new RawStringArgument("poll", false));
        //! /p challenge
        $this->registerArgument(0, new RawStringArgument("challenge", false));

        $this->registerArgument(1, new RawStringArgument("nich_name", false));

    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (sizeof($args) === 0)
            $sender->sendMessage($this->getUsage());
        switch ($args[0]) {
            case "help":
                $sender->sendMessage($this->getUsage());
                break;
            case "invite":

                break;

            case "leave":

                break;

            case "list":

                break;

            case "promote":

                break;

            case "home":

                break;

            case "remove":

                break;

            case "warp":

                break;

            case "accept":

                break;

            case "disband":

                break;

            case "mute":

                break;
        }
    }
}
<?php


namespace mlsdmitry\PartyFriends\friends;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class FCommands extends Command
{

    public function execute(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!isset($args[0]))
            $sender->sendMessage($this->getUsage());
        switch ($args[0]) {
            case "accept":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getUsage());
                    return;
                }
                break;
            case "add":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getUsage());
                    return;
                }
                break;
            case "deny":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getUsage());
                    return;
                }
                break;
            case "help":
                $sender->sendMessage($this->getUsage());
                break;
            case "list":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getUsage());
                    return;
                }
                break;
            case "remove":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getUsage());
                    return;
                }
                break;
            case "requests":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getUsage());
                    return;
                }
                break;
            case "toggle":
                //
                break;
        }
    }
}
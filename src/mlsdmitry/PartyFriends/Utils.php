<?php


namespace mlsdmitry\PartyFriends;


use pocketmine\Player;

class Utils
{
    /**
     * @param string | Player $p_name
     * @return string
     */
    public static function name($p_name)
    {
        return is_object($p_name) ? strtolower(trim($p_name->getName())) : strtolower(trim($p_name));
    }

    /**
     * @param string | Player $p_uuid
     * @return string
     */
    public static function uuid($p_uuid)
    {
        return is_object($p_uuid) ? $p_uuid->getUniqueId()->toString() : $p_uuid;
    }
}
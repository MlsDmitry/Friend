<?php


namespace mlsdmitry\PartyFriends;


use DateTime;
use mlsdmitry\LangAPI\Lang;
use mlsdmitry\PartyFriends\party\events\PartyDisbandEvent;
use mlsdmitry\PartyFriends\party\events\PlayerInvitedToPartyEvent;
use mlsdmitry\PartyFriends\party\events\PlayerPromoteEvent;
use mlsdmitry\PartyFriends\party\Party;
use mlsdmitry\PartyFriends\party\Request;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;
use pocketmine\Server;

class PManager implements Listener
{
    const DONT_HAVE_REQUESTS = 8;
    /**
     * @var array
     */
    private static $parties = [];
    private static $await_accept = [];

    const OWNER = 0;
    const FOLLOWER = 1;
    const NOT_FOUND = 2;

    const DONT_HAVE_PARTY = 3;
    const IS_OFFLINE = 4;
    const IS_NOT_OWNER = 5;
    const AWAIT = 6;

    const PARTY_EXPIRED = 7;


    /*
     ? PARTIES
     * Structure ->
     * [$owner_uuid] => $followers
      $parties[1234-1235-1236-1237] = [$nickname => [$follower_uuid, $follower_obj]]
     */
    /*
     ? AWAIT_ACCEPT
     * Structure ->
     * [$awaiter_uuid][$requester_nickname] =
     ! [
           'owner_uuid' => 1234-1235,
           'owner_obj' => PlayerObj,
           'date' => DateTime,
           'type' => promote | invite
     ! ]
     */


    //----------------------------------API CALLS----------------------------------


    /**
     * @param string | Player $owner_name
     * @return bool
     */
    public static function has_party($owner_name)
    {
        return isset(self::$parties[self::name($owner_name)]);
    }

    /**
     * @param string | Player $p
     * @param null $owner_name
     * @return bool
     */
    public static function is_follower($p, $owner_name = null)
    {
        if (is_null($owner_name)) {
            /**
             * @var string $owner_name
             * @var Party $party
             */
            foreach (self::$parties as $owner_name => $party) {
                if ($party->isFollower(self::uuid($p)))
                    return true;
            }
            return false;
        } else {
            /** @var Party $party */
            $party = self::$parties[self::name($owner_name)];
            $party->isFollower($p);
        }
    }


    /**
     * @param Player | string $p
     * @param array $spec
     * @return bool | int
     */
    public static function validate_player($p, array $spec = [])
    {
        if (is_object($p))
            if (!$p->isOnline())
                return self::IS_OFFLINE;
        if (in_array('has_party', $spec))
            if (!self::has_party($p))
                return self::DONT_HAVE_PARTY;
        return true;
    }

    /**
     * @param string | Player $p_name
     * @return string
     */
    public static function name($p_name)
    {
        return is_object($p_name) ? strtolower($p_name->getName()) : strtolower($p_name);
    }

    /**
     * @param string | Player $p_uuid
     * @return string
     */
    public static function uuid($p_uuid)
    {
        return is_object($p_uuid) ? $p_uuid->getName() : $p_uuid;
    }


    /**
     * @param Player $owner
     * @return bool | void
     */
    public static function disband(Player $owner)
    {
        $causes = self::validate_player($owner, ['has_party']);
        if (!$causes)
            return $causes;
        /** @var Party $party */
        $party = self::$parties[$owner->getName()];

        $ev = new PartyDisbandEvent($party);
        $ev->call();
        unset($party);
    }

    /**
     * @param Player $requester
     * @param $awaiter_name
     * @param int $type
     * @return bool|int
     */
    public static function await_accept(Player $requester, $awaiter_name, int $type)
    {
        $cause = self::validate_player($requester, ['has_party']);
        if ($cause === PManager::DONT_HAVE_PARTY) {
            self::registerParty($requester);
        } elseif ($cause === true) {
        } else {
            return $cause;
        }

        $awaiter = Server::getInstance()->getPlayer($awaiter_name);
        if (is_null($awaiter))
            return self::IS_OFFLINE;

        $r = new Request();
        $r->setRequester($requester);
        $r->setAwaiter($awaiter);
        $r->setType($type);
        self::$await_accept[strtolower($awaiter_name)] = $r;
        return true;
    }


    /**
     * @param string | Player $owner
     * @param Player $follower
     * @return bool|int
     */
    public static function addFollower($owner, Player $follower)
    {
        $cause = self::validate_player($owner, ['has_party']);
        if ($cause === PManager::DONT_HAVE_PARTY) {
            self::registerParty($owner);
        } elseif ($cause === true) {
        } else {
            return $cause;
        }
        /** @var Party $party */
        $party = self::$parties[self::name($owner)];
        $party->addFollower($follower);
        return true;
    }

    /**
     * @param Player $awaiter
     * @param string $requester_name
     * @return int
     */
    public static function accept(Player $awaiter, string $requester_name)
    {
        if (!isset(self::$await_accept[strtolower($awaiter->getName())]))
            return self::DONT_HAVE_REQUESTS;

        /** @var Request $request */
        $request = self::$await_accept[strtolower($awaiter->getName())];

        if (!strtolower($request->getRequester()->getName()) === strtolower($requester_name))
            return self::NOT_FOUND;

        $cause = self::validate_player($request->getRequester(), ['has_party']);
        if ($cause === PManager::DONT_HAVE_PARTY) {
            self::registerParty($request->getRequester());
        } elseif ($cause === true) {
        } else {
            return $cause;
        }
        $now = new DateTime();
        if ($now->diff($request->getDate())->i > PartyFriends::make()->getConfig()->get('party_expire'))
            return self::PARTY_EXPIRED;

        $type = $request->getType();
        /** @var Party $party */
        $party = self::$parties[strtolower($request->getRequester()->getName())];
        if ($type === Request::INVITE_COMMAND) {
            $ev = new PlayerInvitedToPartyEvent($party, $awaiter);
            $ev->call();
            return true;
        } elseif ($type === Request::PROMOTE_COMMAND) {
            $ev = new PlayerPromoteEvent($party, $awaiter);
            $ev->call();
            // Delete party
            unset(self::$parties[strtolower($party->getOwner()->getName())]);
            $followers = $party->getFollowers();
            if (isset($followers[$awaiter->getUniqueId()->toString()]))
                unset($followers[$awaiter->getUniqueId()->toString()]);
            $followers[$party->getOwner()->getUniqueId()->toString()] = $party->getOwner();
            self::registerParty($awaiter, $followers);
            return true;
        }
    }

    /**
     * @param Player $owner
     * @param array $followers
     */
    public static function registerParty(Player $owner, $followers = [])
    {
        self::$parties[strtolower($owner->getName())] = new Party($owner, $followers);
    }

    /**
     * @return array
     */
    public static function getParties(): array
    {
        return self::$parties;
    }
}
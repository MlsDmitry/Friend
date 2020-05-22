<?php


namespace mlsdmitry\PartyFriends\party;


use DateTime;
use mlsdmitry\PartyFriends\party\events\PartyDisbandEvent;
use mlsdmitry\PartyFriends\party\events\PlayerInvitedToPartyEvent;
use mlsdmitry\PartyFriends\party\events\PlayerPromoteEvent;
use mlsdmitry\PartyFriends\party\obj\Party;
use mlsdmitry\PartyFriends\party\obj\Request;
use mlsdmitry\PartyFriends\PartyFriends;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\Server;

class PManager implements Listener
{

    // TODO add already const and logic
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

    const DISBAND_COMMAND = 8;
    const LEAVE_COMMAND = 9;


    /*
     ? PARTIES
     * Structure ->
     * [$owner_uuid] => $followers
      $parties[1234-1235-1236-1237] = [$nickname => [$follower_uuid, $follower_obj]]
     */
    /*˙
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


    /**
     * @param Player $owner
     * @param int $cause
     * @return bool | void
     */
    public static function disband(Player $owner, int $cause = self::DISBAND_COMMAND)
    {
        $causes = self::validate_player($owner, ['has_party']);
        if (!$causes)
            return $causes;
        /** @var Party $party */
        $party = self::$parties[self::name($owner)];

        $ev = new PartyDisbandEvent($party, $cause);
        $ev->call();
        unset(self::$parties[self::name($owner)]);
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
        self::$await_accept[self::name($awaiter_name)] = $r;

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
     * @param Player | string $p_name
     * @return bool|int|array
     */
    public static function get_followers($p_name)
    {
        // validate callable signature
//        Utils::validateCallableSignature(function (Player $follower): void {
//        }, $return_func);

        $cause = self::validate_player($p_name, ['has_party']);
        if ($cause !== true)
            return $cause;

        /** @var Party $party */
        $party = self::$parties[self::name($p_name)];
        $f = $party->getFollowers();
        if ($party->isFollower(self::uuid($p_name))) {
            unset($f[self::uuid($p_name)]);
            $f[$party->getOwner()->getUniqueId()->toString()] = $party->getOwner();
        }
        return $f;
    }

    /**
     * @param Player $awaiter
     * @param string $requester_name
     * @return int
     */
    public static function accept(Player $awaiter, string $requester_name)
    {
        if (!isset(self::$await_accept[self::name($awaiter)]))
            return self::DONT_HAVE_REQUESTS;

        /** @var Request $request */
        $request = self::$await_accept[self::name($awaiter)];

        if (!self::name($request->getRequester()) === self::name($requester_name))
            return self::NOT_FOUND;

        $cause = self::validate_player($request->getRequester(), ['has_party']);
        if ($cause === PManager::DONT_HAVE_PARTY) {
            self::registerParty($request->getRequester());
        } elseif ($cause === true) {
            var_dump('quit');
        } else {
            return $cause;
        }
        $now = new DateTime();
        if ($now->diff($request->getDate())->i > PartyFriends::make()->getConfig()->get('party_expire'))
            return self::PARTY_EXPIRED;

        $type = $request->getType();
        /** @var Party $party */
        $party = self::$parties[self::name($request->getRequester())];
        if ($type === Request::INVITE_COMMAND) {
            $ev = new PlayerInvitedToPartyEvent($party, $awaiter);
            $ev->call();
            // lol I deleted this line in last video :/
            self::addFollower($request->getRequester(), $awaiter);
            print_r(self::$parties);
        } elseif ($type === Request::PROMOTE_COMMAND) {
            $ev = new PlayerPromoteEvent($party, $awaiter);
            $ev->call();
            // Delete party
            unset(self::$parties[self::name($party->getOwner())]);
            $followers = $party->getFollowers();
            if (isset($followers[$awaiter->getUniqueId()->toString()]))
                unset($followers[$awaiter->getUniqueId()->toString()]);
            $followers[$party->getOwner()->getUniqueId()->toString()] = $party->getOwner();
            self::registerParty($awaiter, $followers);
            print_r(self::$parties);
        }
    }

    /**
     * @param Player $owner
     * @param array $followers
     */
    public static function registerParty(Player $owner, $followers = [])
    {
        self::$parties[self::name($owner)] = new Party($owner, $followers);
    }


    /**
     * @param Player $p
     * @return int
     */
    public static function leave(Player $p)
    {
        if (!self::has_party($p))
            return self::DONT_HAVE_PARTY;
        self::disband($p, self::LEAVE_COMMAND);
        return true;
    }

    /**
     * @return array
     */
    public static function getParties(): array
    {
        return self::$parties;
    }
}
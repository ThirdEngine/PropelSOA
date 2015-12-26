<?php

namespace SOA\SOABundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class SOASOABundle extends Bundle
{
    /**
     * this will allow the reverse command to tell which tables belong to this bundle
     */
    public static $defaultTablePrefix = 'soa';

    /**
     * this will define which bundle tables without prefixes should get added to
     */
    public static $defaultBundle = false;
}

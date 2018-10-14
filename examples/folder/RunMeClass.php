<?php

namespace specialnamespace;

/**
 * Class RunMeClass
 * @package specialnamespace
 * @autorunclass
 */
class RunMeClass
{
    /** @var RunMeClass */
    var $instance;

    /**
     * RunMeClass constructor.
     */
    public function __construct()
    {
        echo "This class was created";
    }



}
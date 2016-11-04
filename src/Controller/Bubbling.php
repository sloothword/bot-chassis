<?php

namespace Chassis\Controller;

/**
 * Enum for the bubbling behaviour.
 * Bubbling::BEFORE => Controller gets called before more specific one
 * Bubbling::AFTER => Controller gets called after more specific one
 * Bubbling::NONE => Controller gets only called if no more specific handler available
 */
class Bubbling
{

    const BEFORE = -1;
    const AFTER = 1;
    const NONE = 0;

}

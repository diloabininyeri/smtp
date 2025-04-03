<?php

namespace Zeus\Email;

/**
 *
 */
enum Priority: int
{
    case HIGHEST = 1;
    case HIGH = 2;
    case NORMAL = 3;
    case LOW = 4;
    case LOWEST = 5;
}

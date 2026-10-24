<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Dashboard;

/** Columns of 12 on wide screens: sm 3, md 6, lg 9, full 12 (frontend-behavior §8.6). */
enum WidgetSize: string
{
    case Sm = 'sm';
    case Md = 'md';
    case Lg = 'lg';
    case Full = 'full';
}

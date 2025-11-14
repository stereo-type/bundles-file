<?php

/**
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
declare(strict_types=1);

return [
    'Slcorp\FileBundle' => [
        'type' => 'attribute',
        'is_bundle' => false,
        'dir' => '%kernel.project_dir%/src/Bundles/FileBundle/src/Domain/Entity',
        'prefix' => 'Slcorp\FileBundle\Domain\Entity',
        'alias' => 'Slcorp\FileBundle',
    ],
];

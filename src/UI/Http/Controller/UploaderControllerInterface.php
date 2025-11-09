<?php

/**
 * @package    UploaderControllerInterface.php
 * @copyright  2025 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
declare(strict_types=1);

namespace Slcorp\FileBundle\UI\Http\Controller;

use Slcorp\FileBundle\Application\Enum\FileUILibrary;
use Slcorp\FileBundle\Domain\Entity\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface UploaderControllerInterface
{

    public function library(): FileUILibrary;

    public function upload(Request $request): Response;

    public function delete(Request $request, int $fileId): JsonResponse;

}

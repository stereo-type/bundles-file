<?php

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class      => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class       => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class                => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class        => ['all' => true],
    Sonata\Twig\Bridge\Symfony\SonataTwigBundle::class         => ['all' => true],
    Sonata\Form\Bridge\Symfony\SonataFormBundle::class         => ['all' => true],
    Sonata\Doctrine\Bridge\Symfony\SonataDoctrineBundle::class => ['all' => true],
    Sonata\MediaBundle\SonataMediaBundle::class                => ['all' => true],
    Symfony\Bundle\MakerBundle\MakerBundle::class              => ['dev' => true],
    Vich\UploaderBundle\VichUploaderBundle::class              => ['all' => true],
];

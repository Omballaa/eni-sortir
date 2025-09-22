<?php

namespace App\Form;

use App\Entity\Lieu;
use App\Entity\Ville;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class LieuType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomLieu', TextType::class, [
                'label' => 'Nom du lieu',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Parc des expositions'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le nom du lieu est obligatoire'
                    ]),
                    new Length([
                        'max' => 30,
                        'maxMessage' => 'Le nom du lieu ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('rue', TextType::class, [
                'label' => 'Rue',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 123 rue de la Paix'
                ],
                'constraints' => [
                    new Length([
                        'max' => 30,
                        'maxMessage' => 'La rue ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('latitude', NumberType::class, [
                'label' => 'Latitude',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 47.2184',
                    'step' => 'any'
                ],
                'scale' => 8,
                'constraints' => [
                    new Regex([
                        'pattern' => '/^-?([0-8]?[0-9](\.[0-9]{1,8})?|90(\.0{1,8})?)$/',
                        'message' => 'La latitude doit être comprise entre -90 et 90'
                    ])
                ]
            ])
            ->add('longitude', NumberType::class, [
                'label' => 'Longitude',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: -1.5536',
                    'step' => 'any'
                ],
                'scale' => 8,
                'constraints' => [
                    new Regex([
                        'pattern' => '/^-?(1[0-7][0-9](\.[0-9]{1,8})?|180(\.0{1,8})?|[0-9]?[0-9](\.[0-9]{1,8})?)$/',
                        'message' => 'La longitude doit être comprise entre -180 et 180'
                    ])
                ]
            ])
            ->add('ville', EntityType::class, [
                'class' => Ville::class,
                'choice_label' => function (Ville $ville) {
                    return $ville->getNomVille() . ' (' . $ville->getCodePostal() . ')';
                },
                'label' => 'Ville',
                'attr' => [
                    'class' => 'form-select'
                ],
                'placeholder' => 'Choisir une ville',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner une ville'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lieu::class,
            'attr' => [
                'novalidate' => 'novalidate' // Désactive la validation HTML5 pour utiliser celle de Symfony
            ]
        ]);
    }
}
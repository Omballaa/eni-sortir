<?php

namespace App\Form;

use App\Entity\Ville;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class VilleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomVille', TextType::class, [
                'label' => 'Nom de la ville',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Nantes',
                    'maxlength' => 30
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le nom de la ville est obligatoire.'
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 30,
                        'minMessage' => 'Le nom de la ville doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le nom de la ville ne peut pas dépasser {{ limit }} caractères.'
                    ])
                ]
            ])
            ->add('codePostal', TextType::class, [
                'label' => 'Code postal',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 44000',
                    'maxlength' => 10
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le code postal est obligatoire.'
                    ]),
                    new Regex([
                        'pattern' => '/^[0-9]{5}$/',
                        'message' => 'Le code postal doit contenir exactement 5 chiffres.'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ville::class,
        ]);
    }
}
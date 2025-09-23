<?php

namespace App\Form;

use App\Entity\Site;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class SiteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomSite', TextType::class, [
                'label' => 'Nom du site',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: ENI Nantes',
                    'maxlength' => 30
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le nom du site est obligatoire.'
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 30,
                        'minMessage' => 'Le nom du site doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le nom du site ne peut pas dépasser {{ limit }} caractères.'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Site::class,
        ]);
    }
}
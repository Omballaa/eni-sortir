<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CancelSortieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('motifAnnulation', TextareaType::class, [
                'label' => 'Motif de l\'annulation',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Expliquez le motif de l\'annulation de cette sortie...',
                    'maxlength' => 500
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le motif d\'annulation est obligatoire'
                    ]),
                    new Assert\Length([
                        'min' => 10,
                        'max' => 500,
                        'minMessage' => 'Le motif doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le motif ne peut pas dépasser {{ limit }} caractères'
                    ])
                ],
                'help' => 'Ce motif sera visible par tous les participants. Soyez précis et courtois.'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Ne pas lier le formulaire à une entité spécifique
            // car motifAnnulation n'est pas une propriété de Sortie
            'data_class' => null,
        ]);
    }
}
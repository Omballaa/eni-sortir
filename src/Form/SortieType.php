<?php

namespace App\Form;

use App\Entity\Lieu;
use App\Entity\Sortie;
use App\Entity\Ville;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Range;

class SortieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la sortie',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Nom de votre sortie'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le nom de la sortie est obligatoire']),
                    new Length([
                        'max' => 30,
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('dateHeureDebut', DateTimeType::class, [
                'label' => 'Date et heure de la sortie',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La date de la sortie est obligatoire']),
                    new GreaterThan([
                        'value' => 'now',
                        'message' => 'La date de la sortie doit être dans le futur'
                    ])
                ]
            ])
            ->add('dateLimiteInscription', DateTimeType::class, [
                'label' => 'Date limite d\'inscription',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La date limite d\'inscription est obligatoire']),
                    new Range([
                        'min' => 'today',
                        'max' => 'dateHeureDebut',
                        'notInRangeMessage' => 'Vous devez entrer un date entre aujourd\'hui et la sortie'
                    ])
                ]
            ])
            ->add('nbInscriptionsMax', IntegerType::class, [
                'label' => 'Nombre de places',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le nombre de places est obligatoire']),
                    new Positive(['message' => 'Le nombre de places doit être positif'])
                ]
            ])
            ->add('duree', IntegerType::class, [
                'label' => 'Durée (en minutes)',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1
                ],
                'required' => false,
                'constraints' => [
                    new Positive(['message' => 'La durée doit être positive'])
                ]
            ])
            ->add('infosSortie', TextareaType::class, [
                'label' => 'Description et infos',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Décrivez votre sortie, donnez des informations pratiques...'
                ]
            ])
            ->add('ville', EntityType::class, [
                'class' => Ville::class,
                'choice_label' => function (Ville $ville) {
                    return $ville->getNomVille() . ' (' . $ville->getCodePostal() . ')';
                },
                'placeholder' => 'Choisissez une ville',
                'mapped' => false,
                'label' => 'Ville',
                'attr' => [
                    'class' => 'form-select'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La ville est obligatoire'])
                ]
            ])
            ->add('lieu', EntityType::class, [
                'class' => Lieu::class,
                'choice_label' => 'nomLieu',
                'placeholder' => 'Choisissez d\'abord une ville',
                'label' => 'Lieu',
                'attr' => [
                    'class' => 'form-select'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le lieu est obligatoire'])
                ]
            ])
            ->add('latitude', NumberType::class, [
                'label' => 'Latitude',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'step' => 'any'
                ]
            ])
            ->add('longitude', NumberType::class, [
                'label' => 'Longitude',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'step' => 'any'
                ]
            ]);

        // Gestion dynamique des lieux en fonction de la ville sélectionnée
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $sortie = $event->getData();
                $form = $event->getForm();

                if ($sortie && $sortie->getLieu() && $sortie->getLieu()->getVille()) {
                    $ville = $sortie->getLieu()->getVille();
                    $form->get('ville')->setData($ville);
                    
                    $form->add('lieu', EntityType::class, [
                        'class' => Lieu::class,
                        'choice_label' => 'nomLieu',
                        'choices' => $ville->getLieux(),
                        'label' => 'Lieu',
                        'attr' => [
                            'class' => 'form-select'
                        ],
                        'constraints' => [
                            new NotBlank(['message' => 'Le lieu est obligatoire'])
                        ]
                    ]);
                }
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $data = $event->getData();
                $form = $event->getForm();

                if (isset($data['ville']) && $data['ville']) {
                    $form->add('lieu', EntityType::class, [
                        'class' => Lieu::class,
                        'choice_label' => 'nomLieu',
                        'query_builder' => function ($repository) use ($data) {
                            return $repository->createQueryBuilder('l')
                                ->where('l.ville = :ville')
                                ->setParameter('ville', $data['ville']);
                        },
                        'label' => 'Lieu',
                        'attr' => [
                            'class' => 'form-select'
                        ],
                        'constraints' => [
                            new NotBlank(['message' => 'Le lieu est obligatoire'])
                        ]
                    ]);
                }
            }
        );
               
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
        ]);
    }
}
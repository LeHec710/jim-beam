<?php

namespace App\Form;

use App\Entity\Lottery;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
class LotteryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['data']->getStartAt()) {
            $startAt = $options['data']->getStartAt()->format('d/m/Y H:i');
        } else {
            $startAt = NULL;
        }

        if ($options['data']->getEndAt()) {
            $endAt = $options['data']->getEndAt()->format('d/m/Y H:i');
        } else {
            $endAt = NULL;
        }

        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du tirage',
                'attr' => [
                    'placeholder' => "Nom du tirage",
                ]
            ])
            // ->add('token', TextType::class, [
            //     'required'   => false,
            //     'label' => 'Identifiant unique ( auto. )',
            //     'attr' => [
            //     ]
            // ])
            ->add('startAt', TextType::class, [
                'mapped' => false,
                'required'   => true,
                'data' => $startAt,
                'label' => 'Début le',
                'attr' => [
                    'class' => 'datetimepicker'
                ]
            ])
            ->add('endAt', TextType::class, [
                'mapped' => false,
                'required'   => true,
                'data' => $endAt,
                'label' => 'Tirage le',
                'attr' => [
                    'class' => 'datetimepicker'
                ]
            ])
            ->add('rules', FileType::class, [
                'required' => false,
                'label' => 'Règlement',
                'mapped' => false,
                'required' => false
            ])
            // ->add('picture', FileType::class, [
            //     'label' => 'Icone de l\'opération',
            //     'mapped' => false,
            //     'required' => false
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Lottery::class,
        ]);
    }
}

<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;


use App\Entity\User;
use App\Entity\Club;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['data']->getIsActive() === null) {
            $checked = true;
        } else {
            $checked = $options['data']->getIsActive();
        }
        if ($options['data']->getId() === null) {
            $sendChecked = true;
        } else {
            $sendChecked = false;
        }
        
        $builder
            ->add('lastname', TextType::class, [
                'label' => 'Nom', 
                'attr' => [
                    'placeholder' => 'Le nom du membre',
                    ]])
            ->add('firstname', TextType::class, [
                'label' => 'Prénom', 
                'attr' => [
                    'placeholder' => 'Le prénom du membre',
                    ]])
            ->add('email', EmailType::class, [
                'required' => true,
                'label' => 'Adresse e-mail', 
                'attr' => [
                    'placeholder' => "exemple@societe.com",
                    ]])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => false,
                'empty_data' => null,
                'mapped' => false,
                'invalid_message' => 'Les deux mots de passe sont différents',
                'options' => ['attr' => ['class' => 'password-field']],
                'first_options'  => ['label' => 'Mot de passe'],
                'second_options' => ['label' => 'Confirmation du mot de passe']
            ])        
            ->add('phoneMobile', TextType::class, [
                'required' => false,
                'empty_data' => '',
                'label' => 'Téléphone portable',
                'attr' => [
                    'placeholder' => 'Le numéro de portable',
                    ]])
            ->add('phoneFix', TextType::class, [
                'required' => false,
                'label' => 'Téléphone fixe',
                'attr' => [
                    'placeholder' => 'Le numéro de fixe',
                    ]])
            ->add('function', TextType::class, [
                'required' => false,
                'empty_data' => "",
                'label' => 'Fonction ',
                'attr' => [
                    'placeholder' => 'Fonction',
                    ]])
//            ->add('addressComplement', TextType::class, [
//                'required' => false,
//                'empty_data' => null,
//                'label' => "Complément d'adresse",
//                'attr' => [
//                    'placeholder' => 'Bâtiment, résidence, etc.',
//                    ]])
//            ->add('zip', TextType::class, [
//                'required' => false,
//                'label' => "Code postal",
//                'attr' => [
//                    'placeholder' => '75000, 33000, etc.',
//                    ]])
//            ->add('city', TextType::class, [
//                'required' => false,
//                'label' => "Ville",
//                'attr' => [
//                    'placeholder' => 'Paris, Bordeaux, etc.',
//                    ]])
        ;
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
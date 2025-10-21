<?php

namespace App\Form;

use App\Entity\DamagedEducator;
use App\Entity\Tenant;
use App\Form\DataTransformer\AccountNumberTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DamagedEducatorEditType extends AbstractType
{
    private AccountNumberTransformer $accountNumberTransformer;

    public function __construct()
    {
        $this->accountNumberTransformer = new AccountNumberTransformer();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tenant', EntityType::class, [
                'label' => 'Tenant',
                'class' => Tenant::class,
                'choice_label' => 'name',
                'placeholder' => 'Izaberite ustanovu',
            ])
            ->add('name', TextType::class, [
                'label' => 'Ime',
            ])

            ->add('amount', IntegerType::class, [
                'label' => 'Cifra',
                'attr' => [
                    'min' => 500,
                    'max' => DamagedEducator::MONTHLY_LIMIT,
                ],
            ])
            ->add('accountNumber', TextType::class, [
                'label' => 'Broj računa',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Sačuvaj',
            ]);

        $builder->get('accountNumber')->addModelTransformer($this->accountNumberTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DamagedEducator::class,
            'user' => null,
            'entityManager' => null,
        ]);
    }
}

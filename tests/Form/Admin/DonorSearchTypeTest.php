<?php

namespace App\Tests\Form\Admin;

use App\Form\Admin\DonorSearchType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class DonorSearchTypeTest extends KernelTestCase
{
    public function testDonorSearchFormHasCorrectFields(): void
    {
        self::bootKernel();
        $form = self::getContainer()->get('form.factory')->create(DonorSearchType::class);

        // Check that form contains the expected fields
        $this->assertTrue($form->has('isMonthly'));
        $this->assertTrue($form->has('firstName'));
        $this->assertTrue($form->has('lastName'));
        $this->assertTrue($form->has('email'));
        $this->assertTrue($form->has('tenant'));
        $this->assertTrue($form->has('submit'));

        // Get the form field types
        $this->assertInstanceOf(ChoiceType::class, $form->get('isMonthly')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(TextType::class, $form->get('firstName')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(TextType::class, $form->get('lastName')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(TextType::class, $form->get('email')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(EntityType::class, $form->get('tenant')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(SubmitType::class, $form->get('submit')->getConfig()->getType()->getInnerType());
    }

    public function testSubmitValidData(): void
    {
        $formData = [
            'isMonthly' => true,
            'firstName' => null,
            'lastName' => null,
            'email' => null,
            'tenant' => null,
        ];

        self::bootKernel();
        $form = self::getContainer()->get('form.factory')->create(DonorSearchType::class);

        // Submit the form with test data
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        // Check that the form data was mapped correctly
        $this->assertEquals($formData, $form->getData());
    }

    public function testConfigureOptions(): void
    {
        $form = self::getContainer()->get('form.factory')->create(DonorSearchType::class);
        $options = $form->getConfig()->getOptions();

        // Test that CSRF protection is disabled
        $this->assertFalse($options['csrf_protection']);

        // Test that validation_groups is set to false
        $this->assertEmpty($options['validation_groups']);
    }

    public function testGetBlockPrefix(): void
    {
        $formType = new DonorSearchType();
        $this->assertEquals('', $formType->getBlockPrefix());
    }
}

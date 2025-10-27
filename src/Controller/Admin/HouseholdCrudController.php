<?php

namespace App\Controller\Admin;

use App\Entity\Household;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class HouseholdCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Household::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', 'Nazwa'),
        ];
    }
}

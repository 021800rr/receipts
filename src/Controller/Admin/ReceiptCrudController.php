<?php

namespace App\Controller\Admin;

use App\Entity\Receipt;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\{AssociationField, DateField, IntegerField, TextareaField};

class ReceiptCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Receipt::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('household', 'Gospodarstwo'),
            AssociationField::new('store', 'Sklep'),
            DateField::new('purchaseDate', 'Data zakupu'),
            IntegerField::new('totalAmountGrosze', 'Suma (gr)')
                ->hideOnForm(), // wyliczana z pozycji
            TextareaField::new('notes', 'Uwagi')
                ->hideOnIndex()
                ->setRequired(false),
        ];
    }
}

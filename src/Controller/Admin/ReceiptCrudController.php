<?php

namespace App\Controller\Admin;

use App\Entity\Receipt;
use App\Form\ReceiptLineType;
use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;

final class ReceiptCrudController extends AbstractCrudController
{
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            //->overrideTemplates(['crud/edit' => 'admin/edit.html.twig',]);
            ->setDefaultSort(['purchase_date' => 'DESC']);
    }

    public static function getEntityFqcn(): string
    {
        return Receipt::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('household', 'Gospodarstwo')
            ->setFormTypeOptions([
                'query_builder' => function (EntityRepository $repo) {
                    return $repo->createQueryBuilder('p')
                        ->orderBy('p.name', 'ASC');
                },
            ])
            ->setRequired(true);
        yield AssociationField::new('store', 'Sklep')
            ->setFormTypeOptions([
                'query_builder' => function (EntityRepository $repo) {
                    return $repo->createQueryBuilder('p')
                        ->orderBy('p.name', 'ASC');
                },
            ])
            ->setRequired(true);
        // pole daty - używamy nazwy property encji 'purchase_date' i pozwalamy sortować po tej kolumnie DB
        yield DateField::new('purchase_date', 'Data zakupu')
            ->setRequired(true)
            ->setSortable('purchase_date');
        // Suma nagłówka (zł) – tylko do odczytu na liście; edycję trzymaj po Twojej stronie (liczona z pozycji)
        yield NumberField::new('totalAmount', 'Suma (zł)')
            ->onlyOnIndex()
            ->setNumDecimals(2);
        yield TextField::new('notes', 'Uwagi');

        // >>> TU JEST “MIEJSCE NA POZYCJE” <<<
        yield CollectionField::new('lines', 'Pozycje')
            ->setEntryType(ReceiptLineType::class)
            ->allowAdd(true)
            ->allowDelete(true)
            ->renderExpanded(true)
            ->setFormTypeOptions([
                'by_reference' => false,
                'entry_options' => [
                    'row_attr' => ['data-controller' => 'receipt-line']
                ],
                'attr' => ['data-controller' => 'receipt-line'],
            ])
            ->hideOnIndex();
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addAssetMapperEntry('app')
            ->addAssetMapperEntry('product-search')
            ->addAssetMapperEntry('line-total');
    }
}

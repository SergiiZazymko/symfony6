<?php

namespace App\Controller\Admin;

use App\Entity\Conference;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

/**
 * Class ConferenceCrudController
 * Namespace App\Controller\Admin
 */
class ConferenceCrudController extends AbstractCrudController
{
    /**
     * @return string
     */
    public static function getEntityFqcn(): string
    {
        return Conference::class;
    }

    /**
     * @param Crud $crud
     * @return Crud
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Conference')
            ->setEntityLabelInPlural('Conferences')
            ->setSearchFields(['city', 'year'])
            ->setDefaultSort(['year' => 'DESC'])
        ;
    }

    /**
     * @param Filters $filters
     * @return Filters
     */
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('city'))
            ->add(TextFilter::new('year'))
        ;
    }
}

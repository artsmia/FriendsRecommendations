<?php namespace DMA\Recomendations\Classes\Items;

use Log;
use Dma\Recomendations\Classes\Items\ItemBase;
use Doctrine\DBAL\Query\QueryBuilder;



/**
 * User Item 
 * @author Carlos Arroyo
 *
 */
class UserItem extends ItemBase
{
    /**
     * This item can be editable by CMS admin
     * @var boolean
     */
    public $adminEditable = false;
    
    /**
     * {@inheritDoc}
     * @return string
     */
    public function getKey()
	{
		return 'user';
	}

	/**
     * {@inheritDoc}
     * @return string
	 */
	public function getModel()
	{
	    return '\RainLab\User\Models\User';
	}
	
	/**
	 * {@inheritDoc}
	 * @see \DMA\Recomendations\Classes\Items\ItemBase::addSettingsFields()
	 */
	public function getSettingsFields()
	{
		return [];
	
	}
  
	/**
	 * {@inheritDoc}
	 * @see \DMA\Recomendations\Classes\Items\ItemBase::addFeatures()
	 */
	public function getFeatures()
	{
		return [
		    'activities',
		    'badges'
		];
	}	


	/**
	 * {@inheritDoc}
	 * @see \DMA\Recomendations\Classes\Items\ItemBase::addFilters()
	 */
	public function getFilters()
	{
		return [];
	}	
	
	/**
	 * {@inheritDoc}
	 * @see \DMA\Recomendations\Classes\Items\ItemBase::addWeightFeatures()
	 */
	public function getWeightFeatures()
	{
		return [];
	}
	
	/**
	 * {@inheritDoc}
	 * @see \DMA\Recomendations\Classes\Items\ItemBase::getItemRelations()
	 */
	public function getItemRelations()
	{
	    return [
            'activity' => 'activities',
            'badge'    => 'badges',
	    ];
	}
	
	/**
	 * {@inheritDoc}
	 * @see \DMA\Recomendations\Classes\Items\ItemBase::getUpdateAtEvents()
	 */
	public function getUpdateEvents()
	{
		return [];
	}	
}
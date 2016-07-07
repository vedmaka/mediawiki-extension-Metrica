<?php

/**
 * Metrica SpecialPage for Metrica extension
 *
 * @file
 * @ingroup Extensions
 */
class SpecialMetrica extends SpecialPage
{
    public function __construct()
    {
        parent::__construct( 'Metrica' );
    }

    /**
     * Show the page to the user
     *
     * @param string $sub The subpage string argument (if any).
     *  [[Special:Metrica/subpage]].
     */
    public function execute( $sub )
    {
        $out = $this->getOutput();

	    if( !$this->getUser()->isAllowed('metrica') ) {
	    	$this->displayRestrictionError();
	    }

	    $out->addModules('ext.metrica.special');

        $out->setPageTitle("Metrica Overview");

	    $templateParser = new TemplateParser( __DIR__ . '/../templates/');
	    $data = array();
	    $out->addHTML( $templateParser->processTemplate( 'dashboard', $data ) );

    }

    protected function getGroupName()
    {
        return 'other';
    }
}

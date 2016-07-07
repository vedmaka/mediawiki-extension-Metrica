<?php

/**
 * Hooks for Metrica extension
 *
 * @file
 * @ingroup Extensions
 */
class MetricaHooks
{

	public static function onExtensionLoad()
	{
		
	}

	public static function onResourceLoaderGetConfigVars( &$vars )
	{
		global $wgMetricaExcludeSpecials;
		$vars['wgMetricaExcludeSpecials'] = $wgMetricaExcludeSpecials;
	}

	/**
	 * @param OutputPage $out
	 * @param $skin
	 */
	public static function onBeforePageDisplay( &$out, &$skin )
	{
		$out->addModules('ext.metrica.foo');
	}

	/**
	 * @param DatabaseUpdater $updater
	 */
	public static function onLoadExtensionSchemaUpdates( $updater )
	{
		$updater->addExtensionTable(
			'metrica',
			dirname( __FILE__ ) . '/schema/metrica.sql'
		);
	}

}

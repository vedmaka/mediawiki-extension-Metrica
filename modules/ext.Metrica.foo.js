$( function () {

    var Metrica = function() {
        this.alreadySent = false;
        this.initialize();
    };

    Metrica.prototype.initialize = function() {

        if( mw.config.get('wgMetricaExcludeSpecials') && mw.config.get('wgCanonicalSpecialPageName') ) {
            return false;
        }

        this.entryPoint = mw.config.get('wgServer') + mw.config.get('wgScriptPath') + '/api.php' +
                '?action=metrica&format=json';

        this.metricData = {
            'user': {
                'isLoggedIn': mw.config.get('wgUserId') ? 1 : 0,
                'userId': mw.config.get('wgUserId'),
                'userLanguage': mw.config.get('wgUserLanguage'),
                'userName': mw.config.get('wgUserName')
            },
            'page': {
                'pageId': mw.config.get('wgArticleId'),
                'revisionId': mw.config.get('wgCurRevisionId'),
                'isArticle': mw.config.get('wgIsArticle'),
                'pageName': mw.config.get('wgTitle') || mw.config.get('wgPageName'),
                'categories': mw.config.get('wgCategories') || [],
                'namespace': mw.config.get('wgNamespaceNumber'),
                'isMainPage': mw.config.get('wgIsMainPage') || 0
            },
            'action': mw.config.get('wgAction')
        };

        mw.hook( 'postEdit').add( this.mwPostEdit.bind(this) );
        mw.hook( 'wikipage.content').add( this.mwViewContent.bind(this) );

    };

    Metrica.prototype.mwPostEdit = function() {

        this.metricData.action = 'postedit';
        this.sendEvent();

    };

    Metrica.prototype.mwViewContent = function() {

        this.sendEvent();

    };

    Metrica.prototype.sendEvent = function() {

        if( this.alreadySent ) {
            return;
        }

        this.alreadySent = true;

        $.post( this.entryPoint, { 'metrica': this.metricData }, function( response ) {
            // Generally, do nothing here
        });

    };

    window.Metrica = Metrica;

}() );

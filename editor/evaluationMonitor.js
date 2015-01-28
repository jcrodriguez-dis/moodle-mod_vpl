/**
 * @package VPL. Evaluation monitoring
 * @copyright 2013 onward Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
(function(){

	var VPL_Evaluation=function(options){
		function showErrorMessage(message){
			VPL_Util.showErrorMessage(message,{next:options.next});
		};
		var executionActions={
				'ajaxurl': options.ajaxurl,
				'run': showErrorMessage,
				'getLastAction':function(){return null;},
				'close': options.next,
				'next': options.next,
		};
		VPL_Util.requestAction('evaluate', 'evaluating', {},options.ajaxurl,
				function(response) {
					VPL_Util.webSocketMonitor(response, 'evaluate','evaluating',executionActions);
				},
				showErrorMessage
			);
	};
	VPL_Single_Evaluation= function(options){
		VPL_Util.set_str(options.i18n);
		options.next=function(){
			setTimeout(function(){window.location=options.nexturl;},50);
		};
		VPL_Evaluation(options);
	};
	VPL_Batch_Evaluation= function(options){
		VPL_Util.set_str(options.i18n);
		if(typeof options.student ==='undefined'){
			options.student = 0;
			options.next=function(){
				setTimeout(function(){
					if(options.student<options.ajaxurls.length){	
						VPL_BatchEvaluation(options);
					}
				},50);
			};
		}
		options.ajaxurl = options.ajaxurls[options.student++];
		VPL_BatchIteration(options);
	};
})();

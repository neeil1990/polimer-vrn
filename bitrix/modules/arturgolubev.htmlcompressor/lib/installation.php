<?
if (!class_exists('agInstaHelper')){
	class agInstaHelper {
		public function IncludeAdminFile($m, $p){
			global $APPLICATION, $DOCUMENT_ROOT;
			$APPLICATION->IncludeAdminFile($m, $DOCUMENT_ROOT.$p);
		}
	}
}
?>
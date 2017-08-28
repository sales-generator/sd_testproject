<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

global $APPLICATION;

$aMenuLinksExt = $APPLICATION->IncludeComponent(
	"bitrix:menu.sections", 
	"", 
	array(
		"IS_SEF" => "Y",
		"SEF_BASE_URL" => "/suvenirnaya-produktsiya/",
		"SECTION_PAGE_URL" => "/#SECTION_CODE#/",
		"DETAIL_PAGE_URL" => "#SECTION_ID#/#ELEMENT_ID#",
		"IBLOCK_TYPE" => "catalog",
		"IBLOCK_ID" => "8",
		"DEPTH_LEVEL" => "1",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "36000000",
		"ID" => $_REQUEST["ID"],
		"SECTION_URL" => ""
	),
	false
);

$aMenuLinks = array_merge($aMenuLinks, $aMenuLinksExt);
?>
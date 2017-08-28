<?
IncludeModuleLangFile(__FILE__);

class CAllBlogPost
{
	public static $arSocNetPostPermsCache = array();
	public static $arUACCache = array();
	public static $arBlogPostCache = array();
	public static $arBlogPostIdCache = array();
	public static $arBlogPCCache = array();
	public static $arBlogUCache = array();
	
	const UF_NAME = 'UF_BLOG_POST_DOC';

	function CanUserEditPost($ID, $userID)
	{
		global $APPLICATION;
		$ID = IntVal($ID);
		$userID = IntVal($userID);

		$blogModulePermissions = $APPLICATION->GetGroupRight("blog");
		if ($blogModulePermissions >= "W")
			return True;

		$arPost = CBlogPost::GetByID($ID);
		if (!$arPost)
			return False;

		if (CBlog::IsBlogOwner($arPost["BLOG_ID"], $userID))
			return True;

		$arBlogUser = CBlogUser::GetByID($userID, BLOG_BY_USER_ID);
		if ($arBlogUser && $arBlogUser["ALLOW_POST"] != "Y")
			return False;

		if (CBlogPost::GetBlogUserPostPerms($ID, $userID) < BLOG_PERMS_WRITE)
			return False;

		if ($arPost["AUTHOR_ID"] == $userID)
			return True;

		return False;
	}

	public static function CanUserDeletePost($ID, $userID)
	{
		global $APPLICATION;

		$ID = IntVal($ID);
		$userID = IntVal($userID);

		$blogModulePermissions = $APPLICATION->GetGroupRight("blog");
		if ($blogModulePermissions >= "W")
			return True;

		$arPost = CBlogPost::GetByID($ID);
		if (!$arPost)
			return False;

		if (CBlog::IsBlogOwner($arPost["BLOG_ID"], $userID))
			return True;

		$arBlogUser = CBlogUser::GetByID($userID, BLOG_BY_USER_ID);
		if ($arBlogUser && $arBlogUser["ALLOW_POST"] != "Y")
			return False;

		$perms = CBlogPost::GetBlogUserPostPerms($ID, $userID);
		if ($perms <= BLOG_PERMS_WRITE && $userID != $arPost["AUTHOR_ID"])
			return False;

		if($perms > BLOG_PERMS_WRITE)
			return true;

		if ($arPost["AUTHOR_ID"] == $userID)
			return True;

		return False;
	}

	public static function GetBlogUserPostPerms($ID, $userID)
	{
		global $APPLICATION;

		$ID = IntVal($ID);
		$userID = IntVal($userID);

		$arAvailPerms = array_keys($GLOBALS["AR_BLOG_PERMS"]);
		$blogModulePermissions = $APPLICATION->GetGroupRight("blog");
		if ($blogModulePermissions >= "W")
			return $arAvailPerms[count($arAvailPerms) - 1];

		$arPost = CBlogPost::GetByID($ID);
		if (!$arPost)
			return $arAvailPerms[0];

		if (CBlog::IsBlogOwner($arPost["BLOG_ID"], $userID))
			return $arAvailPerms[count($arAvailPerms) - 1];

		$arBlogUser = CBlogUser::GetByID($userID, BLOG_BY_USER_ID);
		if ($arBlogUser && $arBlogUser["ALLOW_POST"] != "Y")
			return $arAvailPerms[0];

		$arUserGroups = CBlogUser::GetUserGroups($userID, $arPost["BLOG_ID"], "Y", BLOG_BY_USER_ID);

		$perms = CBlogUser::GetUserPerms($arUserGroups, $arPost["BLOG_ID"], $ID, BLOG_PERMS_POST, BLOG_BY_USER_ID);
		if ($perms)
			return $perms;

		return $arAvailPerms[0];
	}

	public static function GetBlogUserCommentPerms($ID, $userID)
	{
		global $APPLICATION;
		$ID = IntVal($ID);
		$userID = IntVal($userID);

		$arAvailPerms = array_keys($GLOBALS["AR_BLOG_PERMS"]);

		$blogModulePermissions = $APPLICATION->GetGroupRight("blog");
		if ($blogModulePermissions >= "W")
			return $arAvailPerms[count($arAvailPerms) - 1];

		if(IntVal($ID) > 0)
		{
			if (!($arPost = CBlogPost::GetByID($ID)))
			{
				return $arAvailPerms[0];
			}
			else
			{
				$arBlog = CBlog::GetByID($arPost["BLOG_ID"]);
				if ($arBlog["ENABLE_COMMENTS"] != "Y")
				{
					return $arAvailPerms[0];
				}

				if (CBlog::IsBlogOwner($arPost["BLOG_ID"], $userID))
				{
					return $arAvailPerms[count($arAvailPerms) - 1];
				}

				$arUserGroups = CBlogUser::GetUserGroups($userID, $arPost["BLOG_ID"], "Y", BLOG_BY_USER_ID);
				$perms = CBlogUser::GetUserPerms($arUserGroups, $arPost["BLOG_ID"], $ID, BLOG_PERMS_COMMENT, BLOG_BY_USER_ID);
				if ($perms)
				{
					return $perms;
				}
			}
		}
		else
		{
			return $arAvailPerms[0];
		}

		if(IntVal($userID) > 0)
		{
			$arBlogUser = CBlogUser::GetByID($userID, BLOG_BY_USER_ID);
			if ($arBlogUser && $arBlogUser["ALLOW_POST"] != "Y")
				return $arAvailPerms[0];
		}

		return $arAvailPerms[0];
	}

	/*************** ADD, UPDATE, DELETE *****************/
	function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $DB, $APPLICATION;

		if ((is_set($arFields, "TITLE") || $ACTION=="ADD") && strlen($arFields["TITLE"]) <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("BLG_GP_EMPTY_TITLE"), "EMPTY_TITLE");
			return false;
		}

		if ((is_set($arFields, "DETAIL_TEXT") || $ACTION=="ADD") && strlen($arFields["DETAIL_TEXT"]) <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("BLG_GP_EMPTY_DETAIL_TEXT"), "EMPTY_DETAIL_TEXT");
			return false;
		}

		if ((is_set($arFields, "BLOG_ID") || $ACTION=="ADD") && IntVal($arFields["BLOG_ID"]) <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("BLG_GP_EMPTY_BLOG_ID"), "EMPTY_BLOG_ID");
			return false;
		}
		elseif (is_set($arFields, "BLOG_ID"))
		{
			$arResult = CBlog::GetByID($arFields["BLOG_ID"]);
			if (!$arResult)
			{
				$APPLICATION->ThrowException(str_replace("#ID#", $arFields["BLOG_ID"], GetMessage("BLG_GP_ERROR_NO_BLOG")), "ERROR_NO_BLOG");
				return false;
			}
		}

		if ((is_set($arFields, "AUTHOR_ID") || $ACTION=="ADD") && IntVal($arFields["AUTHOR_ID"]) <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("BLG_GP_EMPTY_AUTHOR_ID"), "EMPTY_AUTHOR_ID");
			return false;
		}
		elseif (is_set($arFields, "AUTHOR_ID"))
		{
			$dbResult = CUser::GetByID($arFields["AUTHOR_ID"]);
			if (!$dbResult->Fetch())
			{
				$APPLICATION->ThrowException(GetMessage("BLG_GP_ERROR_NO_AUTHOR"), "ERROR_NO_AUTHOR");
				return false;
			}
		}

		if (is_set($arFields, "DATE_CREATE") && (!$DB->IsDate($arFields["DATE_CREATE"], false, LANG, "FULL")))
		{
			$APPLICATION->ThrowException(GetMessage("BLG_GP_ERROR_DATE_CREATE"), "ERROR_DATE_CREATE");
			return false;
		}

		if (is_set($arFields, "DATE_PUBLISH") && (!$DB->IsDate($arFields["DATE_PUBLISH"], false, LANG, "FULL")))
		{
			$APPLICATION->ThrowException(GetMessage("BLG_GP_ERROR_DATE_PUBLISH"), "ERROR_DATE_PUBLISH");
			return false;
		}


		$arFields["PREVIEW_TEXT_TYPE"] = strtolower($arFields["PREVIEW_TEXT_TYPE"]);
		if ((is_set($arFields, "PREVIEW_TEXT_TYPE") || $ACTION=="ADD") && $arFields["PREVIEW_TEXT_TYPE"] != "text" && $arFields["PREVIEW_TEXT_TYPE"] != "html")
			$arFields["PREVIEW_TEXT_TYPE"] = "text";

		//$arFields["DETAIL_TEXT_TYPE"] = strtolower($arFields["DETAIL_TEXT_TYPE"]);
		if ((is_set($arFields, "DETAIL_TEXT_TYPE") || $ACTION=="ADD") && strtolower($arFields["DETAIL_TEXT_TYPE"]) != "text" && strtolower($arFields["DETAIL_TEXT_TYPE"]) != "html")
			$arFields["DETAIL_TEXT_TYPE"] = "text";
		if(strlen($arFields["DETAIL_TEXT_TYPE"]) > 0)
			$arFields["DETAIL_TEXT_TYPE"] = strtolower($arFields["DETAIL_TEXT_TYPE"]);

		$arStatus = array_keys($GLOBALS["AR_BLOG_PUBLISH_STATUS"]);
		if ((is_set($arFields, "PUBLISH_STATUS") || $ACTION=="ADD") && !in_array($arFields["PUBLISH_STATUS"], $arStatus))
			$arFields["PUBLISH_STATUS"] = $arStatus[0];

		if ((is_set($arFields, "ENABLE_TRACKBACK") || $ACTION=="ADD") && $arFields["ENABLE_TRACKBACK"] != "Y" && $arFields["ENABLE_TRACKBACK"] != "N")
			$arFields["ENABLE_TRACKBACK"] = "Y";

		if ((is_set($arFields, "ENABLE_COMMENTS") || $ACTION=="ADD") && $arFields["ENABLE_COMMENTS"] != "Y" && $arFields["ENABLE_COMMENTS"] != "N")
			$arFields["ENABLE_COMMENTS"] = "Y";

		if (!empty($arFields["ATTACH_IMG"]))
		{
			$res = CFile::CheckImageFile($arFields["ATTACH_IMG"], 0, 0, 0);
			if (strlen($res) > 0)
			{
				$APPLICATION->ThrowException(GetMessage("BLG_GP_ERROR_ATTACH_IMG").": ".$res, "ERROR_ATTACH_IMG");
				return false;
			}
		}
		else
			$arFields["ATTACH_IMG"] = false;

		if (is_set($arFields, "NUM_COMMENTS"))
			$arFields["NUM_COMMENTS"] = IntVal($arFields["NUM_COMMENTS"]);
		if (is_set($arFields, "NUM_COMMENTS_ALL"))
			$arFields["NUM_COMMENTS_ALL"] = IntVal($arFields["NUM_COMMENTS_ALL"]);
		if (is_set($arFields, "NUM_TRACKBACKS"))
			$arFields["NUM_TRACKBACKS"] = IntVal($arFields["NUM_TRACKBACKS"]);
		if (is_set($arFields, "FAVORITE_SORT"))
		{
			$arFields["FAVORITE_SORT"] = IntVal($arFields["FAVORITE_SORT"]);
			if($arFields["FAVORITE_SORT"] <= 0)
				$arFields["FAVORITE_SORT"] = false;
		}

		if (is_set($arFields, "CODE") && strlen($arFields["CODE"]) > 0)
		{
			$arFields["CODE"] = preg_replace("/[^a-zA-Z0-9_-]/is", "", Trim($arFields["CODE"]));

			if (in_array(strtolower($arFields["CODE"]), $GLOBALS["AR_BLOG_POST_RESERVED_CODES"]))
			{
				$APPLICATION->ThrowException(str_replace("#CODE#", $arFields["CODE"], GetMessage("BLG_GP_RESERVED_CODE")), "CODE_RESERVED");
				return false;
			}

			$arFilter = Array(
				"CODE" => $arFields["CODE"]
			);
			if(IntVal($ID) > 0)
			{
				$arPost = CBlogPost::GetByID($ID);
				$arFilter["!ID"] = $arPost["ID"];
				$arFilter["BLOG_ID"] = $arPost["BLOG_ID"];
			}
			else
			{
				if(IntVal($arFields["BLOG_ID"]) > 0)
					$arFilter["BLOG_ID"] = $arFields["BLOG_ID"];
			}

			$dbItem = CBlogPost::GetList(Array(), $arFilter, false, Array("nTopCount" => 1), Array("ID", "CODE", "BLOG_ID"));
			if($dbItem->Fetch())
			{
				$APPLICATION->ThrowException(GetMessage("BLG_GP_CODE_EXIST", Array("#CODE#" => $arFields["CODE"])), "CODE_EXIST");
				return false;
			}
		}
		return True;
	}

	function SetPostPerms($ID, $arPerms = array(), $permsType = BLOG_PERMS_POST)
	{
		global $DB;

		$ID = IntVal($ID);
		$permsType = (($permsType == BLOG_PERMS_COMMENT) ? BLOG_PERMS_COMMENT : BLOG_PERMS_POST);
		if(!is_array($arPerms))
			$arPerms = array();

		$arPost = CBlogPost::GetByID($ID);
		if ($arPost)
		{
			$arInsertedGroups = array();
			foreach ($arPerms as $key => $value)
			{
				$dbGroupPerms = CBlogUserGroupPerms::GetList(
					array(),
					array(
						"BLOG_ID" => $arPost["BLOG_ID"],
						"USER_GROUP_ID" => $key,
						"PERMS_TYPE" => $permsType,
						"POST_ID" => $arPost["ID"]
					),
					false,
					false,
					array("ID")
				);
				if ($arGroupPerms = $dbGroupPerms->Fetch())
				{
					CBlogUserGroupPerms::Update(
						$arGroupPerms["ID"],
						array(
							"PERMS" => $value,
							"AUTOSET" => "N"
						)
					);
				}
				else
				{
					CBlogUserGroupPerms::Add(
						array(
							"BLOG_ID" => $arPost["BLOG_ID"],
							"USER_GROUP_ID" => $key,
							"PERMS_TYPE" => $permsType,
							"POST_ID" => $arPost["ID"],
							"AUTOSET" => "N",
							"PERMS" => $value
						)
					);
				}

				$arInsertedGroups[] = $key;
			}

			$dbResult = CBlogUserGroupPerms::GetList(
				array(),
				array(
					"BLOG_ID" => $arPost["BLOG_ID"],
					"PERMS_TYPE" => $permsType,
					"POST_ID" => 0,
					"!USER_GROUP_ID" => $arInsertedGroups
				),
				false,
				false,
				array("ID", "USER_GROUP_ID", "PERMS")
			);
			while ($arResult = $dbResult->Fetch())
			{
				$dbGroupPerms = CBlogUserGroupPerms::GetList(
					array(),
					array(
						"BLOG_ID" => $arPost["BLOG_ID"],
						"USER_GROUP_ID" => $arResult["USER_GROUP_ID"],
						"PERMS_TYPE" => $permsType,
						"POST_ID" => $arPost["ID"]
					),
					false,
					false,
					array("ID")
				);
				if ($arGroupPerms = $dbGroupPerms->Fetch())
				{
					CBlogUserGroupPerms::Update(
						$arGroupPerms["ID"],
						array(
							"PERMS" => $arResult["PERMS"],
							"AUTOSET" => "Y"
						)
					);
				}
				else
				{
					CBlogUserGroupPerms::Add(
						array(
							"BLOG_ID" => $arPost["BLOG_ID"],
							"USER_GROUP_ID" => $arResult["USER_GROUP_ID"],
							"PERMS_TYPE" => $permsType,
							"POST_ID" => $arPost["ID"],
							"AUTOSET" => "Y",
							"PERMS" => $arResult["PERMS"]
						)
					);
				}
			}
		}
	}

	public static function Delete($ID)
	{
		global $DB, $CACHE_MANAGER, $USER_FIELD_MANAGER;

		$ID = IntVal($ID);

		$arPost = CBlogPost::GetByID($ID);
		if ($arPost)
		{
			foreach(GetModuleEvents("blog", "OnBeforePostDelete", true) as $arEvent)
			{
				if (ExecuteModuleEventEx($arEvent, Array($ID))===false)
					return false;
			}

			$dbResult = CBlogComment::GetList(
				array(),
				array("POST_ID" => $ID),
				false,
				false,
				array("ID")
			);
			while ($arResult = $dbResult->Fetch())
			{
				if (!CBlogComment::Delete($arResult["ID"]))
					return False;
			}

			$dbResult = CBlogUserGroupPerms::GetList(
				array(),
				array("POST_ID" => $ID, "BLOG_ID" => $arPost["BLOG_ID"]),
				false,
				false,
				array("ID")
			);
			while ($arResult = $dbResult->Fetch())
			{
				if (!CBlogUserGroupPerms::Delete($arResult["ID"]))
					return False;
			}

			$dbResult = CBlogTrackback::GetList(
				array(),
				array("POST_ID" => $ID, "BLOG_ID" => $arPost["BLOG_ID"]),
				false,
				false,
				array("ID")
			);
			while ($arResult = $dbResult->Fetch())
			{
				if (!CBlogTrackback::Delete($arResult["ID"]))
					return False;
			}

			$dbResult = CBlogPostCategory::GetList(
				array(),
				array("POST_ID" => $ID, "BLOG_ID" => $arPost["BLOG_ID"]),
				false,
				false,
				array("ID")
			);
			while ($arResult = $dbResult->Fetch())
			{
				if (!CBlogPostCategory::Delete($arResult["ID"]))
					return False;
			}

			$strSql =
				"SELECT F.ID ".
				"FROM b_blog_post P, b_file F ".
				"WHERE P.ID = ".$ID." ".
				"	AND P.ATTACH_IMG = F.ID ";
			$z = $DB->Query($strSql, false, "FILE: ".__FILE__." LINE:".__LINE__);
			while ($zr = $z->Fetch())
				CFile::Delete($zr["ID"]);

			CBlogPost::DeleteSocNetPostPerms($ID);

			unset(static::$arBlogPostCache[$ID]);

			$arBlog = CBlog::GetByID($arPost["BLOG_ID"]);

			$result = $DB->Query("DELETE FROM b_blog_post WHERE ID = ".$ID, true);

			if (IntVal($arBlog["LAST_POST_ID"]) == $ID)
				CBlog::SetStat($arPost["BLOG_ID"]);

			if ($result)
			{
				$res = CBlogImage::GetList(array(), array("POST_ID"=>$ID, "IS_COMMENT" => "N"));
				while($aImg = $res->Fetch())
					CBlogImage::Delete($aImg['ID']);
			}
			if ($result)
				$USER_FIELD_MANAGER->Delete("BLOG_POST", $ID);

			foreach(GetModuleEvents("blog", "OnPostDelete", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, Array($ID, &$result));

			if (CModule::IncludeModule("search"))
			{
				CSearch::Index("blog", "P".$ID,
					array(
						"TITLE" => "",
						"BODY" => ""
					)
				);
				//CSearch::DeleteIndex("blog", false, "COMMENT", $arPost["BLOG_ID"]."|".$ID);
			}
			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				$CACHE_MANAGER->ClearByTag("blog_post_".$ID);
			}
			
			return $result;
		}
		else
			return false;
	}

	//*************** SELECT *********************/
	public static function PreparePath($blogUrl, $postID = 0, $siteID = False, $is404 = True, $userID = 0, $groupID = 0)
	{
		$blogUrl = Trim($blogUrl);
		$postID = IntVal($postID);
		$groupID = IntVal($groupID);
		$userID = IntVal($userID);

		if (!$siteID)
		{
			$siteID = SITE_ID;
		}

		$dbPath = CBlogSitePath::GetList(array(), array("SITE_ID"=>$siteID));
		while ($arPath = $dbPath->Fetch())
		{
			if (strlen($arPath["TYPE"]) > 0)
			{
				$arPaths[$arPath["TYPE"]] = $arPath["PATH"];
			}
			else
			{
				$arPaths["OLD"] = $arPath["PATH"];
			}
		}

		if($postID > 0)
		{
			if($groupID > 0)
			{
				if(strlen($arPaths["H"])>0)
				{
					$result = str_replace("#blog#", $blogUrl, $arPaths["H"]);
					$result = str_replace("#post_id#", $postID, $result);
					$result = str_replace("#user_id#", $userID, $result);
					$result = str_replace("#group_id#", $groupID, $result);
				}
				elseif(strlen($arPaths["G"])>0)
				{
					$result = str_replace("#blog#", $blogUrl, $arPaths["G"]);
					$result = str_replace("#user_id#", $userID, $result);
					$result = str_replace("#group_id#", $groupID, $result);
				}
			}
			elseif(strlen($arPaths["P"])>0)
			{
				$result = str_replace("#blog#", $blogUrl, $arPaths["P"]);
				$result = str_replace("#post_id#", $postID, $result);
				$result = str_replace("#user_id#", $userID, $result);
			}
			elseif(strlen($arPaths["B"])>0)
			{
				$result = str_replace("#blog#", $blogUrl, $arPaths["B"]);
				$result = str_replace("#user_id#", $userID, $result);
			}
			else
			{
				if($is404)
					$result = htmlspecialcharsbx($arPaths["OLD"])."/".htmlspecialcharsbx($blogUrl)."/".$postID.".php";
				else
					$result = htmlspecialcharsbx($arPaths["OLD"])."/post.php?blog=".$blogUrl."&post_id=".$postID;
			}
		}
		else
		{
			if(strlen($arPaths["B"])>0)
			{
				$result = str_replace("#blog#", $blogUrl, $arPaths["B"]);
				$result = str_replace("#user_id#", $userID, $result);
			}
			else
			{
				if($is404)
					$result = htmlspecialcharsbx($arPaths["OLD"])."/".htmlspecialcharsbx($blogUrl)."/";
				else
					$result = htmlspecialcharsbx($arPaths["OLD"])."/post.php?blog=".$blogUrl;
			}
		}

		return $result;
	}

	function PreparePath2Post($realUrl, $url, $arParams = array())
	{
		return CBlogPost::PreparePath(
			$url,
			isset($arParams["POST_ID"]) ? $arParams["POST_ID"] : 0,
			isset($arParams["SITE_ID"]) ? $arParams["SITE_ID"] : False
		);
	}

	public static function CounterInc($ID)
	{
		global $DB;
		$ID = IntVal($ID);
		if(!is_array($_SESSION["BLOG_COUNTER"]))
			$_SESSION["BLOG_COUNTER"] = Array();
		if(in_array($ID, $_SESSION["BLOG_COUNTER"]))
			return;
		$_SESSION["BLOG_COUNTER"][] = $ID;
		$strSql =
			"UPDATE b_blog_post SET ".
			"	VIEWS =  ".$DB->IsNull("VIEWS", 0)." + 1 ".
			"WHERE ID=".$ID;
		$DB->Query($strSql);
	}

	public static function Notify($arPost, $arBlog, $arParams)
	{
		global $DB;
		if(empty($arBlog))
		{
			$arBlog = CBlog::GetByID($arPost["BLOG_ID"]);
		}

		$siteId = (!empty($arParams['SITE_ID']) ? $arParams['SITE_ID'] : SITE_ID);

		$arImages = $arOwner = Array();
		$parserBlog = false;
		$text4mail = $serverName = $AuthorName = "";

		if($arParams["bSoNet"] || ($arBlog["EMAIL_NOTIFY"]=="Y" && $arParams["user_id"] != $arBlog["OWNER_ID"]))
		{
			$BlogUser = CBlogUser::GetByID($arParams["user_id"], BLOG_BY_USER_ID);
			$BlogUser = CBlogTools::htmlspecialcharsExArray($BlogUser);
			$res = CUser::GetByID($arBlog["OWNER_ID"]);
			$arOwner = $res->GetNext();
			$dbUser = CUser::GetByID($arParams["user_id"]);
			$arUser = $dbUser->Fetch();
			$AuthorName = CBlogUser::GetUserNameEx($arUser, $BlogUser, $arParams);
			$parserBlog = new blogTextParser(false, $arParams["PATH_TO_SMILE"]);
			$text4mail = $arPost["DETAIL_TEXT"];
			if($arPost["DETAIL_TEXT_TYPE"] == "html")
			{
				$text4mail = HTMLToTxt($text4mail);
			}

			$res = CBlogImage::GetList(array("ID"=>"ASC"),array("POST_ID"=>$arPost["ID"], "BLOG_ID"=>$arBlog["ID"], "IS_COMMENT" => "N"));
			while ($arImage = $res->Fetch())
			{
				$arImages[$arImage['ID']] = $arImage['FILE_ID'];
			}

			$text4mail = $parserBlog->convert4mail($text4mail, $arImages);
			$serverName = ((defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0) ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));
		}

		if (!$arParams["bSoNet"] && $arBlog["EMAIL_NOTIFY"]=="Y" && $arParams["user_id"] != $arBlog["OWNER_ID"] && IntVal($arBlog["OWNER_ID"]) > 0) // Send notification to email
		{
			CEvent::Send(
				"NEW_BLOG_MESSAGE",
				$siteId,
				array(
					"BLOG_ID" => $arBlog["ID"],
					"BLOG_NAME" => htmlspecialcharsBack($arBlog["NAME"]),
					"BLOG_URL" => $arBlog["URL"],
					"MESSAGE_TITLE" => $arPost["TITLE"],
					"MESSAGE_TEXT" => $text4mail,
					"MESSAGE_DATE" => GetTime(MakeTimeStamp($arPost["DATE_PUBLISH"])-CTimeZone::GetOffset(), "FULL"),
					"MESSAGE_PATH" => "http://".$serverName.CComponentEngine::MakePathFromTemplate(htmlspecialcharsBack($arParams["PATH_TO_POST"]), array("blog" => $arBlog["URL"], "post_id" => $arPost["ID"], "user_id" => $arBlog["OWNER_ID"], "group_id" => $arParams["SOCNET_GROUP_ID"])),
					"AUTHOR" => $AuthorName,
					"EMAIL_FROM" => COption::GetOptionString("main","email_from", "nobody@nobody.com"),
					"EMAIL_TO" => $arOwner["EMAIL"]
				)
			);
		}

		if(
			$arParams["bSoNet"] && $arPost["ID"]
			&& CModule::IncludeModule("socialnetwork")
			&& $parserBlog
		)
		{
			if($arPost["DETAIL_TEXT_TYPE"] == "html" && $arParams["allowHTML"] == "Y" && $arBlog["ALLOW_HTML"] == "Y")
			{
				$arAllow = array("HTML" => "Y", "ANCHOR" => "Y", "IMG" => "Y", "SMILES" => "N", "NL2BR" => "N", "VIDEO" => "Y", "QUOTE" => "Y", "CODE" => "Y");
				if($arParams["allowVideo"] != "Y")
				{
					$arAllow["VIDEO"] = "N";
				}
				$text4message = $parserBlog->convert($arPost["DETAIL_TEXT"], false, $arImages, $arAllow);
			}
			else
			{
				$arAllow = array("HTML" => "N", "ANCHOR" => "N", "BIU" => "N", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "TABLE" => "N", "LIST" => "N", "SMILES" => "N", "NL2BR" => "N", "VIDEO" => "N");
				$text4message = $parserBlog->convert($arPost["DETAIL_TEXT"], false, $arImages, $arAllow, array("isSonetLog"=>true));
			}

			$arSoFields = Array(
				"EVENT_ID" => (intval($arPost["UF_BLOG_POST_IMPRTNT"]) > 0 ? "blog_post_important" : "blog_post"),
				"=LOG_DATE" => (
					strlen($arPost["DATE_PUBLISH"]) > 0
						? (
							MakeTimeStamp($arPost["DATE_PUBLISH"], CSite::GetDateFormat("FULL", SITE_ID)) > time()+CTimeZone::GetOffset()
								? $DB->CharToDateFunction($arPost["DATE_PUBLISH"], "FULL", SITE_ID)
								: $DB->CurrentTimeFunction()
						)
						:
						$DB->CurrentTimeFunction()
				),
				"TITLE_TEMPLATE" => "#USER_NAME# ".GetMessage("BLG_SONET_TITLE"),
				"TITLE" => $arPost["TITLE"],
				"MESSAGE" => $text4message,
				"TEXT_MESSAGE" => $text4mail,
				"MODULE_ID" => "blog",
				"CALLBACK_FUNC" => false,
				"SOURCE_ID" => $arPost["ID"],
				"ENABLE_COMMENTS" => (array_key_exists("ENABLE_COMMENTS", $arPost) && $arPost["ENABLE_COMMENTS"] == "N" ? "N" : "Y")
			);

			$arSoFields["RATING_TYPE_ID"] = "BLOG_POST";
			$arSoFields["RATING_ENTITY_ID"] = intval($arPost["ID"]);

			if($arParams["bGroupMode"])
			{
				$arSoFields["ENTITY_TYPE"] = SONET_ENTITY_GROUP;
				$arSoFields["ENTITY_ID"] = $arParams["SOCNET_GROUP_ID"];
				$arSoFields["URL"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_POST"], array("blog" => $arBlog["URL"], "user_id" => $arBlog["OWNER_ID"], "group_id" => $arParams["SOCNET_GROUP_ID"], "post_id" => $arPost["ID"]));
			}
			else
			{
				$arSoFields["ENTITY_TYPE"] = SONET_ENTITY_USER;
				$arSoFields["ENTITY_ID"] = $arBlog["OWNER_ID"];
				$arSoFields["URL"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_POST"], array("blog" => $arBlog["URL"], "user_id" => $arBlog["OWNER_ID"], "group_id" => $arParams["SOCNET_GROUP_ID"], "post_id" => $arPost["ID"]));
			}

			if (intval($arParams["user_id"]) > 0)
			{
				$arSoFields["USER_ID"] = $arParams["user_id"];
			}

			$logID = CSocNetLog::Add($arSoFields, false);

			if (intval($logID) > 0)
			{
				$socnetPerms = CBlogPost::GetSocNetPermsCode($arPost["ID"]);
				if(!in_array("U".$arPost["AUTHOR_ID"], $socnetPerms))
				{
					$socnetPerms[] = "U".$arPost["AUTHOR_ID"];
				}
				$socnetPerms[] = "SA"; // socnet admin

				if (
					in_array("AU", $socnetPerms) 
					|| in_array("G2", $socnetPerms)
				)
				{
					$socnetPermsAdd = array();

					foreach($socnetPerms as $perm_tmp)
					{
						if (preg_match('/^SG(\d+)$/', $perm_tmp, $matches))
						{
							if (
								!in_array("SG".$matches[1]."_".SONET_ROLES_USER, $socnetPerms)
								&& !in_array("SG".$matches[1]."_".SONET_ROLES_MODERATOR, $socnetPerms)
								&& !in_array("SG".$matches[1]."_".SONET_ROLES_OWNER, $socnetPerms)
							)
							{
								$socnetPermsAdd[] = "SG".$matches[1]."_".SONET_ROLES_USER;
							}
						}
					}
					if (count($socnetPermsAdd) > 0)
					{
						$socnetPerms = array_merge($socnetPerms, $socnetPermsAdd);
					}
				}

				CSocNetLog::Update($logID, array("TMP_ID" => $logID));
				if (CModule::IncludeModule("extranet"))
				{
					CSocNetLog::Update($logID, array(
						"SITE_ID" => CExtranet::GetSitesByLogDestinations($socnetPerms, $arPost["AUTHOR_ID"], $siteId)
					));
				}

				CSocNetLogRights::DeleteByLogID($logID);
				CSocNetLogRights::Add($logID, $socnetPerms);

				if (\Bitrix\Main\Loader::includeModule('crm'))
				{
					CCrmLiveFeedComponent::processCrmBlogPostRights($logID, $arSoFields, $arPost, 'new');
				}

				\Bitrix\Main\FinderDestTable::merge(array(
					"CONTEXT" => "blog_post",
					"CODE" => \Bitrix\Main\FinderDestTable::convertRights($socnetPerms, array("U".$arPost["AUTHOR_ID"]))
				));

				$arUsrId = array();
				$bForAll = (in_array("AU", $socnetPerms) || in_array("G2", $socnetPerms));
				if (!$bForAll)
				{
					foreach($socnetPerms as $code)
					{

						if (preg_match('/^U(\d+)$/', $code, $matches))
						{
							$arUsrId[] = $matches[1];
						}
						elseif (!in_array($code, array("SA")))
						{
							$arUsrId = array();
							break;
						}
					}
				}

				CSocNetLog::CounterIncrement(array(
					"ENTITY_ID" => $logID,
					"EVENT_ID" => $arSoFields["EVENT_ID"],
					"TYPE" => "L",
					"FOR_ALL_ACCESS" =>  $bForAll,
					"USERS_TO_PUSH" => (
						$bForAll
						|| empty($arUsrId)
						|| count($arUsrId) > 20
							? array()
							: $arUsrId
					),
					"SEND_TO_AUTHOR" => (
						!empty($arParams["SEND_COUNTER_TO_AUTHOR"])
						&& $arParams["SEND_COUNTER_TO_AUTHOR"] == "Y"
							? "Y"
							: "N"
					)
				));

				return $logID;
			}
		}
	}

	public static function UpdateLog($postID, $arPost, $arBlog, $arParams)
	{
		if (!CModule::IncludeModule('socialnetwork'))
		{
			return;
		}

		$parserBlog = new blogTextParser(false, $arParams["PATH_TO_SMILE"]);

		preg_match("#^(.*?)<cut[\s]*(/>|>).*?$#is", $arPost["DETAIL_TEXT"], $arMatches);
		if (count($arMatches) <= 0)
		{
			preg_match("#^(.*?)\[cut[\s]*(/\]|\]).*?$#is", $arPost["DETAIL_TEXT"], $arMatches);
		}

		$cut_suffix = (count($arMatches) > 0 ? "#CUT#" : "");

		$arImages = Array();
		$res = CBlogImage::GetList(array("ID"=>"ASC"),array("POST_ID"=>$postID, "BLOG_ID"=>$arBlog["ID"], "IS_COMMENT" => "N"));
		while ($arImage = $res->Fetch())
		{
			$arImages[$arImage['ID']] = $arImage['FILE_ID'];
		}

		if($arPost["DETAIL_TEXT_TYPE"] == "html" && $arParams["allowHTML"] == "Y" && $arBlog["ALLOW_HTML"] == "Y")
		{
			$arAllow = array("HTML" => "Y", "ANCHOR" => "Y", "IMG" => "Y", "SMILES" => "N", "NL2BR" => "N", "VIDEO" => "Y", "QUOTE" => "Y", "CODE" => "Y");
			if($arParams["allowVideo"] != "Y")
			{
				$arAllow["VIDEO"] = "N";
			}
			$text4message = $parserBlog->convert($arPost["DETAIL_TEXT"], true, $arImages, $arAllow);
		}
		else
		{
			$arAllow = array("HTML" => "N", "ANCHOR" => "N", "BIU" => "N", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "TABLE" => "N", "LIST" => "N", "SMILES" => "N", "NL2BR" => "N", "VIDEO" => "N");
			$text4message = $parserBlog->convert($arPost["DETAIL_TEXT"], true, $arImages, $arAllow, array("isSonetLog"=>true));
		}

		$text4message .= $cut_suffix;

		$arSoFields = array(
			"TITLE_TEMPLATE" => "#USER_NAME# ".GetMessage("BLG_SONET_TITLE"),
			"TITLE" => $arPost["TITLE"],
			"MESSAGE" => $text4message,
			"TEXT_MESSAGE" => $text4message,
			"ENABLE_COMMENTS" => (array_key_exists("ENABLE_COMMENTS", $arPost) && $arPost["ENABLE_COMMENTS"] == "N" ? "N" : "Y"),
			"EVENT_ID" => (intval($arPost["UF_BLOG_POST_IMPRTNT"]) > 0 ? "blog_post_important" : "blog_post")
		);

		$dbRes = CSocNetLog::GetList(
			array("ID" => "DESC"),
			array(
				"EVENT_ID" => array("blog_post", "blog_post_important"),
				"SOURCE_ID" => $postID
			),
			false,
			false,
			array("ID", "ENTITY_TYPE", "ENTITY_ID", "EVENT_ID", "USER_ID")
		);
		if ($arLog = $dbRes->Fetch())
		{
			CSocNetLog::Update($arLog["ID"], $arSoFields);
			$socnetPerms = CBlogPost::GetSocNetPermsCode($postID);
			if(!in_array("U".$arPost["AUTHOR_ID"], $socnetPerms))
			{
				$socnetPerms[] = "U".$arPost["AUTHOR_ID"];
			}
			if (CModule::IncludeModule("extranet"))
			{
				CSocNetLog::Update($arLog["ID"], array(
					"SITE_ID" => CExtranet::GetSitesByLogDestinations($socnetPerms, $arPost["AUTHOR_ID"])
				));
			}
			$socnetPerms[] = "SA"; // socnet admin
			CSocNetLogRights::DeleteByLogID($arLog["ID"]);
			CSocNetLogRights::Add($arLog["ID"], $socnetPerms);

			if (\Bitrix\Main\Loader::includeModule('crm'))
			{
				CCrmLiveFeedComponent::processCrmBlogPostRights($arLog["ID"], $arLog, $arPost, 'edit');
			}
		}
	}

	public static function DeleteLog($postID, $bMicroblog = false)
	{
		if (!CModule::IncludeModule('socialnetwork'))
			return;

		$dbComment = CBlogComment::GetList(
			array(),
			array(
				"POST_ID" => $postID,
			),
			false,
			false,
			array("ID")
		);

		while ($arComment = $dbComment->Fetch())
		{
			$dbRes = CSocNetLog::GetList(
				array("ID" => "DESC"),
				array(
					"EVENT_ID" => Array("blog_comment", "blog_comment_micro"),
					"SOURCE_ID" => $arComment["ID"]
				),
				false,
				false,
				array("ID")
			);
			while ($arRes = $dbRes->Fetch())
				CSocNetLog::Delete($arRes["ID"]);
		}

		$dbRes = CSocNetLog::GetList(
			array("ID" => "DESC"),
			array(
				"EVENT_ID" => array("blog_post_micro", "blog_post", "blog_post_important"),
				"SOURCE_ID" => $postID
			),
			false,
			false,
			array("ID")
		);
		while ($arRes = $dbRes->Fetch())
			CSocNetLog::Delete($arRes["ID"]);
	}

	public static function GetID($code, $blogID)
	{
		$postID = false;
		$blogID = IntVal($blogID);

		$code = preg_replace("/[^a-zA-Z0-9_-]/is", "", Trim($code));
		if(strlen($code) <= 0 || IntVal($blogID) <= 0)
			return false;

		if (
			!empty(static::$arBlogPostIdCache[$blogID."_".$code])
			&& IntVal(static::$arBlogPostIdCache[$blogID."_".$code]) > 0)
		{
			return static::$arBlogPostIdCache[$blogID."_".$code];
		}
		else
		{
			$arFilter = Array("CODE" => $code);
			if(IntVal($blogID) > 0)
				$arFilter["BLOG_ID"] = $blogID;
			$dbPost = CBlogPost::GetList(Array(), $arFilter, false, Array("nTopCount" => 1), Array("ID"));
			if($arPost = $dbPost->Fetch())
			{
				static::$arBlogPostIdCache[$blogID."_".$code] = $arPost["ID"];
				$postID = $arPost["ID"];
			}
		}

		return $postID;
	}

	public static function GetPostID($postID, $code, $allowCode = false)
	{
		$postID = IntVal($postID);
		$code = preg_replace("/[^a-zA-Z0-9_-]/is", "", Trim($code));
		if(strlen($code) <= 0 && IntVal($postID) <= 0)
			return false;

		if($allowCode && strlen($code) > 0)
			return $code;

		return $postID;
	}

	function AddSocNetPerms($ID, $perms = array(), $arPost = array())
	{
		global $CACHE_MANAGER;

		if(IntVal($ID) <= 0)
			return false;

		$arResult = Array();

		// D - department
		// U - user
		// SG - socnet group
		// DR - department and hier
		// G - user group
		// AU - authorized user
		// CRMCONTACT - CRM contact
		//$bAU = false;

		if(empty($perms) || in_array("UA", $perms))//if default rights or for everyone
		{
			CBlogPost::__AddSocNetPerms($ID, "U", $arPost["AUTHOR_ID"], "US".$arPost["AUTHOR_ID"]); // for myself
			$perms1 = CBlogPost::GetSocnetGroups("U", $arPost["AUTHOR_ID"]);
			foreach($perms1 as $val)
			{
				if(strlen($val) > 0)
				{
					CBlogPost::__AddSocNetPerms($ID, "U", $arPost["AUTHOR_ID"], $val);

					if(!in_array($val, $arResult))
					{
						$arResult[] = $val;
					}
				}
			}
		}
		if(!empty($perms))
		{
			$perms = array_unique($perms);

			foreach($perms as $val)
			{
				if($val == "UA")
				{
					continue;
				}

				if(strlen($val) > 0)
				{
					if (
						preg_match('/^(CRMCONTACT)(\d+)$/i', $val, $matches)
						|| preg_match('/^(DR)(\d+)$/i', $val, $matches)
						|| preg_match('/^(SG)(\d+)$/i', $val, $matches)
						|| preg_match('/^(AU)(\d+)$/i', $val, $matches)
						|| preg_match('/^(U)(\d+)$/i', $val, $matches)
						|| preg_match('/^(D)(\d+)$/i', $val, $matches)
						|| preg_match('/^(G)(\d+)$/i', $val, $matches)
					)
					{
						$scT = $matches[1];
						$scID = $matches[2];
					}
					else
					{
						continue;
					}

					if($scT == "SG")
					{
						$permsNew = CBlogPost::GetSocnetGroups("G", $scID);
						foreach($permsNew as $val1)
						{
							CBlogPost::__AddSocNetPerms($ID, $scT, $scID, $val1);
							if(!in_array($val1, $arResult))
							{
								$arResult[] = $val1;
							}
						}
					}

					CBlogPost::__AddSocNetPerms($ID, $scT, $scID, $val);
					if(!in_array($val, $arResult))
					{
						$arResult[] = $val;
					}
				}
			}
		}

		BXClearCache(true, "/blog/getsocnetperms/".$ID."/");
		if(defined("BX_COMP_MANAGED_CACHE"))
		{
			$CACHE_MANAGER->ClearByTag("blog_post_getsocnetperms_".$ID);
		}

		return $arResult;
	}

	public static function UpdateSocNetPerms($ID, $perms = array(), $arPost = array())
	{
		global $DB;
		$ID = IntVal($ID);
		if($ID <= 0)
		{
			return false;
		}

		$strSql = "DELETE FROM b_blog_socnet_rights WHERE POST_ID=".$ID;
		$DB->Query($strSql);

		return CBlogPost::AddSocNetPerms($ID, $perms, $arPost);
	}

	function __AddSocNetPerms($ID, $entityType = "", $entityID = 0, $entity)
	{
		global $DB;

		static $allowedTypes = false;

		if ($allowedTypes === false)
		{
			$allowedTypes = Array("D", "U", "SG", "DR", "G", "AU");
			if (IsModuleInstalled('crm'))
			{
				$allowedTypes[] = "CRMCONTACT";
			}
		}

		if(IntVal($ID) > 0 && strlen($entityType) > 0 && strlen($entity) > 0 && in_array($entityType, $allowedTypes))
		{
			$arSCFields = Array("POST_ID" => $ID, "ENTITY_TYPE" => $entityType, "ENTITY_ID" => IntVal($entityID), "ENTITY" => $entity);
			$arSCInsert = $DB->PrepareInsert("b_blog_socnet_rights", $arSCFields);

			if (strlen($arSCInsert[0]) > 0)
			{
				$strSql =
					"INSERT INTO b_blog_socnet_rights(".$arSCInsert[0].") ".
					"VALUES(".$arSCInsert[1].")";
				$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);
				return true;
			}
		}
		return false;
	}

	public static function GetSocNetGroups($entity_type, $entity_id, $operation = "view_post")
	{
		$entity_id = IntVal($entity_id);
		if($entity_id <= 0)
			return false;
		if(!CModule::IncludeModule("socialnetwork"))
			return false;
		$feature = "blog";

		$arResult = array();

		if($entity_type == "G")
		{
			$prefix = "SG".$entity_id."_";
			$letter = CSocNetFeaturesPerms::GetOperationPerm(SONET_ENTITY_GROUP, $entity_id, $feature, $operation);
			switch($letter)
			{
				case SONET_ROLES_ALL:
					$arResult[] = 'O'.$prefix.SONET_ROLES_ALL;
					$arResult[] = 'O'.$prefix.SONET_ROLES_AUTHORIZED;
					$arResult[] = $prefix.SONET_ROLES_USER;
					$arResult[] = $prefix.SONET_ROLES_MODERATOR;
					$arResult[] = $prefix.SONET_ROLES_OWNER;
					break;
				case SONET_ROLES_AUTHORIZED:
					$arResult[] = 'O'.$prefix.SONET_ROLES_AUTHORIZED;
					$arResult[] = $prefix.SONET_ROLES_USER;
					$arResult[] = $prefix.SONET_ROLES_MODERATOR;
					$arResult[] = $prefix.SONET_ROLES_OWNER;
					break;
				case SONET_ROLES_USER:
					$arResult[] = $prefix.SONET_ROLES_USER;
					$arResult[] = $prefix.SONET_ROLES_MODERATOR;
					$arResult[] = $prefix.SONET_ROLES_OWNER;
					break;
				case SONET_ROLES_MODERATOR:
					$arResult[] = $prefix.SONET_ROLES_MODERATOR;
					$arResult[] = $prefix.SONET_ROLES_OWNER;
					break;
				case SONET_ROLES_OWNER:
					$arResult[] = $prefix.SONET_ROLES_OWNER;
					break;
			}
		}
		else
		{
			$prefix = "SU".$entity_id."_";
			$letter = CSocNetFeaturesPerms::GetOperationPerm(SONET_ENTITY_USER, $entity_id, $feature, $operation);
			switch($letter)
			{
				case "A"://All
					$arResult[] = 'G2';
					break;
				case "C"://Authorized
					$arResult[] = 'AU';
					break;
				case "E"://Friends of friends (has no rights yet) so it counts as
				case "M"://Friends
					$arResult[] = $prefix.'M';
					break;
				case "Z"://Personal
					$arResult[] = $prefix.'Z';
					break;
			}
		}

		return $arResult;
	}

	public static function GetSocNetPerms($ID)
	{
		global $DB, $CACHE_MANAGER;
		$ID = IntVal($ID);
		if($ID <= 0)
			return false;

		$arResult = array();

		$cacheTtl = defined("BX_COMP_MANAGED_CACHE") ? 3153600 : 3600*4;
		$cacheId = 'blog_post_getsocnetperms_'.$ID;
		$cacheDir = '/blog/getsocnetperms/'.$ID;

		$obCache = new CPHPCache;
		if($obCache->InitCache($cacheTtl, $cacheId, $cacheDir))
		{
			$arResult = $obCache->GetVars();
		}
		else
		{
			$obCache->StartDataCache();

			$strSql = "SELECT SR.ENTITY_ID, SR.ENTITY_TYPE, SR.ENTITY FROM b_blog_socnet_rights SR
				INNER JOIN b_blog_post P ON (P.ID = SR.POST_ID)
				WHERE SR.POST_ID=".$ID." ORDER BY SR.ENTITY ASC";
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			while($arRes = $dbRes->Fetch())
			{
				$arResult[$arRes["ENTITY_TYPE"]][$arRes["ENTITY_ID"]][] = $arRes["ENTITY"];
			}

			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				$CACHE_MANAGER->StartTagCache($cacheDir);
				$CACHE_MANAGER->RegisterTag("blog_post_getsocnetperms_".$ID);
				$CACHE_MANAGER->EndTagCache();
			}
			$obCache->EndDataCache($arResult);
		}

		return $arResult;
	}

	public static function GetSocNetPermsName($ID)
	{
		global $DB;
		$ID = IntVal($ID);
		if($ID <= 0)
			return false;

		$arResult = Array();
		$strSql = "SELECT SR.ENTITY_TYPE, SR.ENTITY_ID, SR.ENTITY,
						U.NAME as U_NAME, U.LAST_NAME as U_LAST_NAME, U.SECOND_NAME as U_SECOND_NAME, U.LOGIN as U_LOGIN, U.PERSONAL_PHOTO as U_PERSONAL_PHOTO, U.EXTERNAL_AUTH_ID as U_EXTERNAL_AUTH_ID,
						EL.NAME as EL_NAME
					FROM b_blog_socnet_rights SR
					INNER JOIN b_blog_post P
						ON (P.ID = SR.POST_ID)
					LEFT JOIN b_user U
						ON (U.ID = SR.ENTITY_ID AND SR.ENTITY_TYPE = 'U')
					LEFT JOIN b_iblock_section EL
						ON (EL.ID = SR.ENTITY_ID AND SR.ENTITY_TYPE = 'DR' AND EL.ACTIVE = 'Y')
					WHERE
						SR.POST_ID = ".$ID;
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while($arRes = $dbRes->GetNext())
		{
			if(!is_array($arResult[$arRes["ENTITY_TYPE"]][$arRes["ENTITY_ID"]]))
				$arResult[$arRes["ENTITY_TYPE"]][$arRes["ENTITY_ID"]] = $arRes;
			if(!is_array($arResult[$arRes["ENTITY_TYPE"]][$arRes["ENTITY_ID"]]["ENTITY"]))
				$arResult[$arRes["ENTITY_TYPE"]][$arRes["ENTITY_ID"]]["ENTITY"] = Array();
			$arResult[$arRes["ENTITY_TYPE"]][$arRes["ENTITY_ID"]]["ENTITY"][] = $arRes["ENTITY"];
		}
		return $arResult;
	}

	public static function GetSocNetPermsCode($ID)
	{
		global $DB;
		$ID = IntVal($ID);
		if($ID <= 0)
			return false;

		$arResult = Array();
		$strSql = "SELECT SR.ENTITY FROM b_blog_socnet_rights SR
						INNER JOIN b_blog_post P ON (P.ID = SR.POST_ID)
						WHERE SR.POST_ID=".$ID."
						ORDER BY SR.ENTITY ASC";
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while($arRes = $dbRes->Fetch())
		{
			if(!in_array($arRes["ENTITY"], $arResult))
				$arResult[] = $arRes["ENTITY"];
		}
		return $arResult;
	}

	public static function ChangeSocNetPermission($entity_type, $entity_id, $operation)
	{
		global $DB;
		$entity_id = IntVal($entity_id);
		$perms = CBlogPost::GetSocnetGroups($entity_type, $entity_id, $operation);
		$type = "U";
		$type2 = "US";
		if($entity_type == "G")
			$type = $type2 = "SG";
		$DB->Query("DELETE FROM b_blog_socnet_rights
					WHERE
						ENTITY_TYPE = '".$type."'
						AND ENTITY_ID = ".$entity_id."
						AND ENTITY <> '".$type2.$entity_id."'
						AND ENTITY <> '".$type.$entity_id."'
						", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		foreach($perms as $val)
		{
			$DB->Query("INSERT INTO b_blog_socnet_rights (POST_ID, ENTITY_TYPE, ENTITY_ID, ENTITY)
						SELECT SR.POST_ID, SR.ENTITY_TYPE, SR.ENTITY_ID, '".$DB->ForSql($val)."' FROM b_blog_socnet_rights SR
						WHERE SR.ENTITY = '".$type2.$entity_id."'", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
	}

	public static function GetSocNetPostsPerms($entity_type, $entity_id)
	{
		global $DB;
		$entity_id = IntVal($entity_id);
		if($entity_id <= 0)
			return false;

		$type = "U";
		$type2 = "US";
		if($entity_type == "G")
			$type = $type2 = "SG";

		$arResult = Array();
		$dbRes = $DB->Query("
			SELECT SR.POST_ID, SR.ENTITY, SR.ENTITY_ID, SR.ENTITY_TYPE FROM b_blog_socnet_rights SR
			WHERE
				SR.POST_ID IN (SELECT POST_ID FROM b_blog_socnet_rights WHERE ENTITY_TYPE='".$type."' AND ENTITY_ID=".$entity_id." AND ENTITY = '".$type.$entity_id."')
				AND SR.ENTITY <> '".$type2.$entity_id."'
		", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while($arRes = $dbRes->Fetch())
		{
			$arResult[$arRes["POST_ID"]]["PERMS"][] = $arRes["ENTITY"];
			$arResult[$arRes["POST_ID"]]["PERMS_FULL"][$arRes["ENTITY_TYPE"].$arRes["ENTITY_ID"]] = Array("TYPE" => $arRes["ENTITY_TYPE"], "ID" => $arRes["ENTITY_ID"]);
		}
		return $arResult;
	}

	public static function GetSocNetPostPerms($postId = 0, $bNeedFull = false, $userId = false, $postAuthor = 0)
	{
		global $USER;

		$cId = md5(serialize(func_get_args()));

		if (
			is_array($postId)
			&& isset($postId["POST_ID"])
		)
		{
			$arParams = $postId;
			$postId = intval($arParams["POST_ID"]);
			$bNeedFull = (isset($arParams["NEED_FULL"]) ? $arParams["NEED_FULL"] : false);
			$userId = (isset($arParams["USER_ID"]) ? $arParams["USER_ID"] : false);
			$postAuthor = (isset($arParams["POST_AUTHOR_ID"]) ? $arParams["POST_AUTHOR_ID"] : 0);
			$bPublic = (isset($arParams["PUBLIC"]) ? $arParams["PUBLIC"] : false);
			$logId = (isset($arParams["LOG_ID"]) ? intval($arParams["PUBLIC"]) : false);
			$bIgnoreAdmin = (isset($arParams["IGNORE_ADMIN"]) ? $arParams["IGNORE_ADMIN"] : false);
		}
		else
		{
			$bPublic = $logId = $bIgnoreAdmin = false;
		}

		if(!$userId)
		{
			$userId = IntVal($USER->GetID());
			$bByUserId = false;
		}
		else
		{
			$userId = IntVal($userId);
			$bByUserId = true;
		}
		$postId = IntVal($postId);
		if($postId <= 0)
		{
			return false;
		}

		if (!empty(static::$arSocNetPostPermsCache[$cId]))
		{
			return static::$arSocNetPostPermsCache[$cId];
		}

		if (!CModule::IncludeModule("socialnetwork"))
		{
			return false;
		}

		$perms = BLOG_PERMS_DENY;
		$arAvailPerms = array_keys($GLOBALS["AR_BLOG_PERMS"]);

		if(!$bByUserId)
		{
			if (CSocNetUser::IsCurrentUserModuleAdmin())
			{
				$perms = $arAvailPerms[count($arAvailPerms) - 1]; // max
			}
		}
		elseif(
			!$bIgnoreAdmin
			&& CSocNetUser::IsUserModuleAdmin($userId)
		)
		{
			$perms = $arAvailPerms[count($arAvailPerms) - 1]; // max
		}

		if(IntVal($postAuthor) <= 0)
		{
			$dbPost = CBlogPost::GetList(array(), array("ID" => $postId), false, false, array("ID", "AUTHOR_ID"));
			$arPost = $dbPost->Fetch();
		}
		else
		{
			$arPost["AUTHOR_ID"] = $postAuthor;
		}

		if($arPost["AUTHOR_ID"] == $userId)
		{
			$perms = BLOG_PERMS_FULL;
		}

		if($perms <= BLOG_PERMS_DENY)
		{
			$arPerms = CBlogPost::GetSocNetPerms($postId);

			if (
				intval($userId) > 0
				&& IsModuleInstalled('mail')
			) // check for email authorization users
			{
				$rsUsers = CUser::GetList(
					($by="ID"),
					($order="asc"),
					array(
						"ID" => $userId
					),
					array(
						"FIELDS" => array("ID", "EXTERNAL_AUTH_ID"),
						"SELECT" => array("UF_DEPARTMENT")
					)
				);

				if($arUser = $rsUsers->Fetch())
				{
					if ($arUser["EXTERNAL_AUTH_ID"] == 'email')
					{
						return (
							isset($arPerms["U"])
							&& isset($arPerms["U"][$userId])
								? BLOG_PERMS_WRITE
								: BLOG_PERMS_DENY
						);
					}
					elseif (
						$bPublic
						&& (
							!is_array($arUser["UF_DEPARTMENT"])
							|| empty($arUser["UF_DEPARTMENT"])
							|| intval($arUser["UF_DEPARTMENT"][0]) <= 0
						)
						&& CModule::IncludeModule('extranet')
						&& ($extranet_site_id = CExtranet::GetExtranetSiteID()) // for extranet users in public section
					)
					{
						if ($logId)
						{
							$arPostSite = array();
							$rsLogSite = CSocNetLog::GetSite($logId);
							while ($arLogSite = $rsLogSite->Fetch())
							{
								$arPostSite[] = $arLogSite["LID"];
							}

							if (!in_array($extranet_site_id, $arPostSite))
							{
								return BLOG_PERMS_DENY;
							}
						}
						else
						{
							return BLOG_PERMS_DENY;
						}
					}
				}
				else
				{
					return BLOG_PERMS_DENY;
				}
			}

			$arEntities = Array();
			if (!empty(static::$arUACCache[$userId]))
			{
				$arEntities = static::$arUACCache[$userId];
			}
			else
			{
				$arCodes = CAccess::GetUserCodesArray($userId);
				foreach($arCodes as $code)
				{
					if (
						preg_match('/^DR([0-9]+)/', $code, $match)
						|| preg_match('/^D([0-9]+)/', $code, $match)
						|| preg_match('/^IU([0-9]+)/', $code, $match)
					)
					{
						$arEntities["DR"][$code] = $code;
					}
					elseif (preg_match('/^SG([0-9]+)_([A-Z])/', $code, $match))
					{
						$arEntities["SG"][$match[1]][$match[2]] = $match[2];
					}
				}
				static::$arUACCache[$userId] = $arEntities;
			}

			foreach($arPerms as $t => $val)
			{
				foreach($val as $id => $p)
				{
					if(!is_array($p))
					{
						$p = array();
					}
					if($userId > 0 && $t == "U" && $userId == $id)
					{
						$perms = BLOG_PERMS_WRITE;
						if(in_array("US".$userId, $p)) // if author
							$perms = BLOG_PERMS_FULL;
						break;
					}
					if(in_array("G2", $p))
					{
						$perms = BLOG_PERMS_WRITE;
						break;
					}
					if($userId > 0 && in_array("AU", $p))
					{
						$perms = BLOG_PERMS_WRITE;
						break;
					}
					if($t == "SG")
					{
						if(!empty($arEntities["SG"][$id]))
						{
							foreach($arEntities["SG"][$id] as $gr)
							{
								if(in_array("SG".$id."_".$gr, $p))
								{
									$perms = BLOG_PERMS_READ;
									break;
								}
							}
						}
					}

					if($t == "DR" && !empty($arEntities["DR"]))
					{
						if(in_array("DR".$id, $arEntities["DR"]))
						{
							$perms = BLOG_PERMS_WRITE;
							break;
						}
					}
				}

				if($perms > BLOG_PERMS_DENY)
				{
					break;
				}
			}

			if(
				$perms <= BLOG_PERMS_READ
				&& !empty($arPerms['SG'])
			) // check OSG
			{
				$bOpenedSGFound = false;
				foreach($arPerms['SG'] as $arSGPerm)
				{
					if (!empty($arSGPerm))
					{
						foreach($arSGPerm as $sgPerm)
						{
							if (preg_match('/^OSG(\d+)_'.(!$userId ? SONET_ROLES_ALL : SONET_ROLES_AUTHORIZED).'$/', $sgPerm, $matches))
							{
								$bOpenedSGFound = true;
								break;
							}
						}

						if ($bOpenedSGFound)
						{
							break;
						}
					}
				}

				if ($bOpenedSGFound)
				{
					$perms = BLOG_PERMS_READ;
				}
			}

			if(
				$bNeedFull
				&& $perms < BLOG_PERMS_FULL
			)
			{
				$arGroupsId = Array();
				if(!empty($arPerms["SG"]))
				{
					foreach($arPerms["SG"] as $gid => $val)
					{
						if(!empty($arEntities["SG"][$gid]))
							$arGroupsId[] = $gid;
					}
				}

				$operation = Array("full_post", "moderate_post", "write_post", "premoderate_post");
				if(!empty($arGroupsId))
				{
					foreach($operation as $v)
					{
						if($perms <= BLOG_PERMS_READ)
						{
							$f = CSocNetFeaturesPerms::GetOperationPerm(SONET_ENTITY_GROUP, $arGroupsId, "blog", $v);
							if(is_array($f))
							{
								foreach($f as $gid => $val)
								{
									if(in_array($val, $arEntities["SG"][$gid]))
									{
										switch($v)
										{
											case "full_post":
												$perms = BLOG_PERMS_FULL;
												break;
											case "moderate_post":
												$perms = BLOG_PERMS_MODERATE;
												break;
											case "write_post":
												$perms = BLOG_PERMS_WRITE;
												break;
											case "premoderate_post":
												$perms = BLOG_PERMS_PREMODERATE;
												break;
										}
									}
								}
							}
						}
					}
				}
			}
		}

		static::$arSocNetPostPermsCache[$cId] = $perms;

		return $perms;
	}

	public static function NotifyIm($arParams)
	{
		$arUserIDSent = array();

		if (!CModule::IncludeModule("im"))
		{
			return $arUserIDSent;
		}

		$arUsers = array();

		if(!empty($arParams["TO_USER_ID"]))
		{
			foreach($arParams["TO_USER_ID"] as $val)
			{
				$val = IntVal($val);
				if (
					$val > 0
					&& $val != $arParams["FROM_USER_ID"]
				)
				{
					$arUsers[] = $val;
				}
			}
		}
		if(!empty($arParams["TO_SOCNET_RIGHTS"]))
		{
			foreach($arParams["TO_SOCNET_RIGHTS"] as $v)
			{
				if(substr($v, 0, 1) == "U")
				{
					$u = IntVal(substr($v, 1));
					if (
						$u > 0 
						&& !in_array($u, $arUsers) 
						&& (
							!array_key_exists("U", $arParams["TO_SOCNET_RIGHTS_OLD"]) 
							|| empty($arParams["TO_SOCNET_RIGHTS_OLD"]["U"][$u])
						)
						&& $u != $arParams["FROM_USER_ID"]
					)
					{
						$arUsers[] = $u;
					}
				}
			}
		}

		if (!empty($arUsers))
		{
			$rsUser = \Bitrix\Main\UserTable::getList(array(
				'order' => array(),
				'filter' => array(
					"ID" => $arUsers,
					"ACTIVE" => "Y",
					"!=EXTERNAL_AUTH_ID" => 'email'
				),
				'select' => array("ID")
			));

			$arUsers = array();

			while ($arUser = $rsUser->fetch())
			{
				$arUsers[] = $arUser["ID"];
			}
		}

		$arMessageFields = array(
			"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
			"TO_USER_ID" => "",
			"FROM_USER_ID" => $arParams["FROM_USER_ID"],
			"NOTIFY_TYPE" => IM_NOTIFY_FROM,
			"NOTIFY_ANSWER" => "Y",
			"NOTIFY_MODULE" => "blog",
			"PARSE_LINK" => "N"
		);

		$aditGM = $authorName = $authorAvatarUrl = "";
		if(IntVal($arParams["FROM_USER_ID"]) > 0)
		{
			$dbUser = CUser::GetByID($arParams["FROM_USER_ID"]);
			if($arUser = $dbUser->Fetch())
			{
				if($arUser["PERSONAL_GENDER"] == "F")
				{
					$aditGM = "_FEMALE";
				}

				if (!empty($arUser["PERSONAL_PHOTO"]))
				{
					$avatarSize = (isset($arParams["PUSH_AVATAR_SIZE"]) && intval($arParams["PUSH_AVATAR_SIZE"]) > 0 ? intval($arParams["PUSH_AVATAR_SIZE"]) : 100);
					$imageResized = CFile::resizeImageGet(
						$arUser["PERSONAL_PHOTO"],
						array(
							"width" => $avatarSize,
							"height" => $avatarSize
						),
						BX_RESIZE_IMAGE_EXACT
					);
					if ($imageResized)
					{
						$authorAvatarUrl = $imageResized["src"];
					}
				}

				$authorName = (
					$arUser
						? CUser::FormatName(CSite::GetNameFormat(), $arUser, true)
						: GetMessage("BLG_GP_PUSH_USER")
				);
			}
		}

		if (CModule::IncludeModule("socialnetwork"))
		{
			$rsLog = CSocNetLog::GetList(
				array(),
				array(
					"EVENT_ID" => array("blog_post", "blog_post_important", "blog_post_micro"),
					"SOURCE_ID" => $arParams["ID"]
				),
				false,
				false,
				array("ID")
			);
			if ($arLog = $rsLog->Fetch())
			{
				$arMessageFields["LOG_ID"] = $arLog["ID"];
			}
		}

		$arTitle = self::processNotifyTitle($arParams["TITLE"]);
		$arParams["TITLE"] = $arTitle['TITLE'];
		$arParams["TITLE_OUT"] = $arTitle['TITLE_OUT'];
		$bTitleEmpty = $arTitle['IS_TITLE_EMPTY'];

		$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0) ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));

		if (IsModuleInstalled("extranet"))
		{
			$user_path = COption::GetOptionString("socialnetwork", "user_page", false, SITE_ID);
			if (
				strlen($user_path) > 0
				&& strpos($arParams["URL"], $user_path) === 0
			)
			{
				$arParams["URL"] = str_replace($user_path, "#USER_PATH#", $arParams["URL"]);
			}
		}

		// notify mentioned users
		if(!empty($arParams["MENTION_ID"]))
		{
			if(!is_array($arParams["MENTION_ID_OLD"]))
			{
				$arParams["MENTION_ID_OLD"] = Array();
			}

			$arUserIdToMention = $arUserIdToShare = $arNewRights = array();

			foreach($arParams["MENTION_ID"] as $val)
			{
				$val = IntVal($val);
				if (
					IntVal($val) > 0
					&& !in_array($val, $arParams["MENTION_ID_OLD"])
					&& $val != $arParams["FROM_USER_ID"]
				)
				{
					$postPerm = CBlogPost::GetSocNetPostPerms(array(
						"POST_ID" => $arParams["ID"],
						"NEED_FULL" => true,
						"USER_ID" => $val,
						"IGNORE_ADMIN" => true
					));

					if (
						$postPerm < BLOG_PERMS_PREMODERATE
						&& $arParams["TYPE"] == "COMMENT"
					)
					{
						$arUserIdToShare[] = $val;
					}

					if (
						$postPerm >= BLOG_PERMS_READ
						|| $arParams["TYPE"] == "COMMENT"
					)
					{
						$arUserIdToMention[] = $val;
					}
				}
			}

			$arUserIdToMention = array_unique($arUserIdToMention);
			$arUserIdToShare = array_unique($arUserIdToShare);

			foreach($arUserIdToShare as $val)
			{
				$arParams["TO_SOCNET_RIGHTS"][] = 'U'.$val;
				$arNewRights[] = 'U'.$val;
			}

			if (!empty($arUserIdToShare))
			{
				$arPost = CBlogPost::GetByID($arParams["ID"]);
				$arSocnetPerms = CBlogPost::GetSocnetPerms($arPost["ID"]);
				$arSocNetRights = $arNewRights;

				foreach($arSocnetPerms as $entityType => $arEntities)
				{
					foreach($arEntities as $entityId => $arRights)
					{
						$arSocNetRights = array_merge($arSocNetRights, $arRights);
					}
				}

				$arSocNetRights = array_unique($arSocNetRights);

				\Bitrix\Socialnetwork\ComponentHelper::processBlogPostShare(
					array(
						"POST_ID" => $arParams["ID"],
						"BLOG_ID" => $arPost["BLOG_ID"],
						"SITE_ID" => SITE_ID,
						"SONET_RIGHTS" => $arSocNetRights,
						"NEW_RIGHTS" => $arNewRights,
						"USER_ID" => $arParams["FROM_USER_ID"]
					),
					array(
						"PATH_TO_USER" => COption::GetOptionString("main", "TOOLTIP_PATH_TO_USER", '/company/personal/user/#user_id#/', SITE_ID),
						"PATH_TO_POST" => COption::GetOptionString("socialnetwork", "userblogpost_page", '/company/personal/user/#user_id#/blog/#post_id#', SITE_ID),
						"NAME_TEMPLATE" => CSite::GetNameFormat(),
						"SHOW_LOGIN" => "Y",
						"LIVE" => "N"
					)
				);

				if (
					isset($arParams["COMMENT_ID"])
					&& intval($arParams["COMMENT_ID"]) > 0
				)
				{
					$res = CSocNetLogComments::GetList(
						array(),
						array(
							"EVENT_ID" => "blog_comment",
							"SOURCE_ID" => $arParams["COMMENT_ID"]
						),
						false,
						false,
						array("ID", "LOG_ID")
					);

					if ($arSonetLogComment = $res->Fetch())
					{
						$commentId = intval($arSonetLogComment["ID"]);
						if ($commentId > 0)
						{
							CUserCounter::IncrementWithSelect(
								CSocNetLogCounter::GetSubSelect2(
									$commentId,
									array(
										"TYPE" => "LC",
										"MULTIPLE" => "Y",
										"SET_TIMESTAMP" => "Y",
										"USER_ID" => $arUserIdToShare
									)
								),
								true,
								array(
									"SET_TIMESTAMP" => "Y",
									"USERS_TO_PUSH" => $arUserIdToShare
								)
							);
						}
					}
				}
			}

			foreach($arUserIdToMention as $val)
			{
				$val = IntVal($val);
				$arMessageFields["TO_USER_ID"] = $val;

				if (IsModuleInstalled("extranet"))
				{
					$arTmp = CSocNetLogTools::ProcessPath(
						array(
							"URL" => $arParams["URL"],
						),
						$val,
						SITE_ID
					);
					$url = $arTmp["URLS"]["URL"];

					$serverName = (
						strpos($url, "http://") === 0
						|| strpos($url, "https://") === 0
							? ""
							: $arTmp["SERVER_NAME"]
					);
				}
				else
				{
					$url = $arParams["URL"];
				}

				$arMessageFields["PUSH_PARAMS"] = array(
					"ACTION" => "mention"
				);

				if (!empty($authorAvatarUrl))
				{
					$arMessageFields["PUSH_PARAMS"]["ADVANCED_PARAMS"] = array(
						'avatarUrl' => $authorAvatarUrl,
						'senderName' => $authorName
					);
				}

				if ($arParams["TYPE"] == "POST")
				{
					$arMessageFields["NOTIFY_EVENT"] = "mention";
					$arMessageFields["NOTIFY_TAG"] = "BLOG|POST_MENTION|".$arParams["ID"];

					if (!$bTitleEmpty)
					{
						$arMessageFields["NOTIFY_MESSAGE"] = GetMessage(
							"BLG_GP_IM_6".$aditGM,
							array(
								"#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arParams["TITLE"])."</a>"
							)
						);
						$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage(
								"BLG_GP_IM_6".$aditGM,
								array(
									"#title#" => htmlspecialcharsbx($arParams["TITLE_OUT"])
								)
						)." ".$serverName.$url."";
						$arMessageFields["PUSH_MESSAGE"] = GetMessage(
							"BLG_GP_PUSH_6".$aditGM,
							array(
								"#name#" => htmlspecialcharsbx($authorName),
								"#title#" => htmlspecialcharsbx($arParams["TITLE"])
							)
						);
					}
					else
					{
						$arMessageFields["NOTIFY_MESSAGE"] = GetMessage(
							"BLG_GP_IM_6A".$aditGM,
							array(
								"#post#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".GetMessage("BLG_GP_IM_6B")."</a>"
							)
						);
						$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage(
							"BLG_GP_IM_6A".$aditGM,
							array(
								"#post#" => GetMessage("BLG_GP_IM_6B")
							)
						)." ".$serverName.$url."";
						$arMessageFields["PUSH_MESSAGE"] = GetMessage(
							"BLG_GP_PUSH_6A".$aditGM,
							array(
								"#name#" => htmlspecialcharsbx($authorName),
								"#post#" => GetMessage("BLG_GP_IM_6B")
							)
						);
					}
				}
				elseif ($arParams["TYPE"] == "COMMENT")
				{
					$arMessageFields["NOTIFY_EVENT"] = "mention_comment";
					$arMessageFields["NOTIFY_TAG"] = "BLOG|COMMENT_MENTION|".$arParams["ID"].(!empty($arParams["COMMENT_ID"]) ? '|'.$arParams["COMMENT_ID"] : '');

					$commentCropped = truncateText($arParams["BODY"], 100);

					if (!$bTitleEmpty)
					{
						$arMessageFields["NOTIFY_MESSAGE"] = GetMessage(
							"BLG_GP_IM_71".$aditGM,
							array(
								"#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arParams["TITLE"])."</a>",
								"#comment#" => $commentCropped
							)
						);
						$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage(
							"BLG_GP_IM_71".$aditGM,
							array(
								"#title#" => htmlspecialcharsbx($arParams["TITLE_OUT"]),
								"#comment#" => $arParams["BODY"]
							)
						)." ".$serverName.$url."";
						$arMessageFields["PUSH_MESSAGE"] = GetMessage(
							"BLG_GP_PUSH_71".$aditGM,
							array(
								"#name#" => htmlspecialcharsbx($authorName),
								"#title#" => htmlspecialcharsbx($arParams["TITLE"]),
								"#comment#" => $commentCropped
							)
						);
					}
					else
					{
						$arMessageFields["NOTIFY_MESSAGE"] = GetMessage(
							"BLG_GP_IM_71A".$aditGM,
							array(
								"#post#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".GetMessage("BLG_GP_IM_7B")."</a>",
								"#comment#" => $commentCropped
							)
						);
						$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage(
							"BLG_GP_IM_71A".$aditGM,
							array(
								"#post#" => GetMessage("BLG_GP_IM_7B"),
								"#comment#" => $arParams["BODY"]
							)
						)." ".$serverName.$url."";
						$arMessageFields["PUSH_MESSAGE"] = GetMessage(
							"BLG_GP_PUSH_71A".$aditGM,
							array(
								"#name#" => htmlspecialcharsbx($authorName),
								"#post#" => GetMessage("BLG_GP_IM_7B"),
								"#comment#" => $commentCropped
							)
						);
					}
				}

				$arMessageFields["PUSH_PARAMS"]["TAG"] = $arMessageFields["NOTIFY_TAG"];

				$ID = CIMNotify::Add($arMessageFields);
				$arUserIDSent[] = $val;

				if (
					intval($ID) > 0
					&& intval($arMessageFields["LOG_ID"]) > 0
				)
				{
					foreach(GetModuleEvents("blog", "OnBlogPostMentionNotifyIm", true) as $arEvent)
					{
						ExecuteModuleEventEx($arEvent, Array($ID, $arMessageFields));
					}
				}
			}
		}


		// notify 'to' users and an author
		if (!empty($arUsers))
		{
			if($arParams["TYPE"] == "POST")
			{
				$arMessageFields["PUSH_PARAMS"] = array(
					"ACTION" => "post"
				);

				if (!empty($authorAvatarUrl))
				{
					$arMessageFields["PUSH_PARAMS"]["ADVANCED_PARAMS"] = array(
						'avatarUrl' => $authorAvatarUrl,
						'senderName' => $authorName
					);
				}

				$arMessageFields["NOTIFY_EVENT"] = "post";
				$arMessageFields["NOTIFY_TAG"] = "BLOG|POST|".$arParams["ID"];

				if (!$bTitleEmpty)
				{
					$arMessageFields["NOTIFY_MESSAGE"] = GetMessage(
						"BLG_GP_IM_1".$aditGM,
						array(
							"#title#" => "<a href=\"".$arParams["URL"]."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arParams["TITLE"])."</a>"
						)
					);
					$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage(
							"BLG_GP_IM_1".$aditGM,
							array(
								"#title#" => htmlspecialcharsbx($arParams["TITLE_OUT"])
							)
						)." ".$serverName.$arParams["URL"]."";
					$arMessageFields["PUSH_MESSAGE"] = GetMessage(
						"BLG_GP_PUSH_1".$aditGM,
						array(
							"#name#" => $authorName,
							"#title#" => $arParams["TITLE"]
						)
					);
				}
				else
				{
					$arMessageFields["NOTIFY_MESSAGE"] = GetMessage(
						"BLG_GP_IM_1A".$aditGM,
						array(
							"#post#" => "<a href=\"".$arParams["URL"]."\" class=\"bx-notifier-item-action\">".GetMessage("BLG_GP_IM_1B")."</a>"
						)
					);
					$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage(
							"BLG_GP_IM_1A".$aditGM,
							array(
								"#post#" => GetMessage("BLG_GP_IM_1B")
							)
						)." ".$serverName.$arParams["URL"]."";
					$arMessageFields["PUSH_MESSAGE"] = GetMessage(
						"BLG_GP_PUSH_1A".$aditGM,
						array(
							"#name#" => htmlspecialcharsbx($authorName),
							"#post#" => GetMessage("BLG_GP_IM_1B")
						)
					);
				}
			}
			elseif($arParams["TYPE"] == "COMMENT")
			{
				$arMessageFields["PUSH_PARAMS"] = array(
					"ACTION" => "comment"
				);

				if (!empty($authorAvatarUrl))
				{
					$arMessageFields["PUSH_PARAMS"]["ADVANCED_PARAMS"] = array(
						'avatarUrl' => $authorAvatarUrl,
						'senderName' => $authorName
					);
				}

				$arMessageFields["NOTIFY_EVENT"] = "comment";
				$arMessageFields["NOTIFY_TAG"] = "BLOG|COMMENT|".$arParams["ID"].(!empty($arParams["COMMENT_ID"]) ? '|'.$arParams["COMMENT_ID"] : '');

				$commentCropped = truncateText($arParams["BODY"], 100);

				if (!$bTitleEmpty)
				{
					$arMessageFields["NOTIFY_MESSAGE"] = GetMessage(
						"BLG_GP_IM_41".$aditGM,
						array(
							"#title#" => "<a href=\"".$arParams["URL"]."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arParams["TITLE"])."</a>",
							"#comment#" => $commentCropped
						)
					);
					$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage(
							"BLG_GP_IM_41".$aditGM,
							array(
								"#title#" => htmlspecialcharsbx($arParams["TITLE_OUT"]),
								"#comment#" => $arParams["BODY"]
							)
						)." ".$serverName.$arParams["URL"]."\n\n".$arParams["BODY"];
					$arMessageFields["PUSH_MESSAGE"] = GetMessage(
						"BLG_GP_PUSH_41".$aditGM,
						array(
							"#name#" => htmlspecialcharsbx($authorName),
							"#title#" => htmlspecialcharsbx($arParams["TITLE"]),
							"#comment#" => $commentCropped
						)
					);

					$arMessageFields["NOTIFY_MESSAGE_AUTHOR"] = GetMessage(
						"BLG_GP_IM_51".$aditGM,
						array(
							"#title#" => "<a href=\"".$arParams["URL"]."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arParams["TITLE"])."</a>",
							"#comment#" => $commentCropped
						)
					);
					$arMessageFields["NOTIFY_MESSAGE_AUTHOR_OUT"] = GetMessage(
							"BLG_GP_IM_51".$aditGM,
							array(
								"#title#" => htmlspecialcharsbx($arParams["TITLE_OUT"]),
								"#comment#" => $arParams["BODY"]
							)
						)." ".$serverName.$arParams["URL"]."\n\n".$arParams["BODY"];
					$arMessageFields["PUSH_MESSAGE_AUTHOR"] = GetMessage(
						"BLG_GP_PUSH_51".$aditGM,
						array(
							"#name#" => htmlspecialcharsbx($authorName),
							"#title#" => htmlspecialcharsbx($arParams["TITLE"]),
							"#comment#" => $commentCropped
						)
					);
				}
				else
				{
					$arMessageFields["NOTIFY_MESSAGE"] = GetMessage(
						"BLG_GP_IM_41A".$aditGM,
						array(
							"#post#" => "<a href=\"".$arParams["URL"]."\" class=\"bx-notifier-item-action\">".GetMessage("BLG_GP_IM_4B")."</a>",
							"#comment#" => $commentCropped
						)
					);
					$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage(
							"BLG_GP_IM_41A".$aditGM,
							array(
								"#post#" => GetMessage("BLG_GP_IM_4B"),
								"#comment#" => $arParams["BODY"]
							)
						)." ".$serverName.$arParams["URL"]."\n\n".$arParams["BODY"];
					$arMessageFields["PUSH_MESSAGE"] = GetMessage(
						"BLG_GP_PUSH_41A".$aditGM,
						array(
							"#name#" => htmlspecialcharsbx($authorName),
							"#post#" => GetMessage("BLG_GP_IM_4B"),
							"#comment#" => $commentCropped
						)
					);

					$arMessageFields["NOTIFY_MESSAGE_AUTHOR"] = GetMessage(
						"BLG_GP_IM_51A".$aditGM,
						array(
							"#post#" => "<a href=\"".$arParams["URL"]."\" class=\"bx-notifier-item-action\">".GetMessage("BLG_GP_IM_5B")."</a>",
							"#comment#" => $commentCropped
						)
					);
					$arMessageFields["NOTIFY_MESSAGE_AUTHOR_OUT"] = GetMessage(
							"BLG_GP_IM_51A".$aditGM,
							Array(
								"#post#" => GetMessage("BLG_GP_IM_5B"),
								"#comment#" => $arParams["BODY"]
							)
						)." ".$serverName.$arParams["URL"]."\n\n".$arParams["BODY"];
					$arMessageFields["PUSH_MESSAGE_AUTHOR"] = GetMessage(
						"BLG_GP_PUSH_51A".$aditGM,
						array(
							"#name#" => htmlspecialcharsbx($authorName),
							"#post#" => GetMessage("BLG_GP_IM_5B"),
							"#comment#" => $commentCropped
						)
					);
				}
			}
			elseif($arParams["TYPE"] == "SHARE")
			{
				$arMessageFields["PUSH_PARAMS"] = array(
					"ACTION" => "share"
				);

				if (!empty($authorAvatarUrl))
				{
					$arMessageFields["PUSH_PARAMS"]["ADVANCED_PARAMS"] = array(
						'avatarUrl' => $authorAvatarUrl,
						'senderName' => $authorName
					);
				}

				$arMessageFields["NOTIFY_EVENT"] = "share";
				$arMessageFields["NOTIFY_TAG"] = "BLOG|SHARE|".$arParams["ID"];
				if (!$bTitleEmpty)
				{
					$arMessageFields["NOTIFY_MESSAGE"] = GetMessage(
						"BLG_GP_IM_8".$aditGM,
						array(
							"#title#" => "<a href=\"".$arParams["URL"]."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arParams["TITLE"])."</a>"
						)
					);
					$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage(
							"BLG_GP_IM_8".$aditGM,
							Array(
								"#title#" => htmlspecialcharsbx($arParams["TITLE_OUT"])
							)
						)." ".$serverName.$arParams["URL"]."";
					$arMessageFields["PUSHMESSAGE"] = GetMessage(
						"BLG_GP_PUSH_8".$aditGM,
						array(
							"#name#" => htmlspecialcharsbx($authorName),
							"#title#" => htmlspecialcharsbx($arParams["TITLE"])
						)
					);
				}
				else
				{
					$arMessageFields["NOTIFY_MESSAGE"] = GetMessage(
						"BLG_GP_IM_8A".$aditGM,
						array(
							"#post#" => "<a href=\"".$arParams["URL"]."\" class=\"bx-notifier-item-action\">".GetMessage("BLG_GP_IM_8B")."</a>"
						)
					);
					$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage(
							"BLG_GP_IM_8A".$aditGM,
							array(
								"#post#" => GetMessage("BLG_GP_IM_8B")
							)
						)." ".$serverName.$arParams["URL"]."";
					$arMessageFields["PUSH_MESSAGE"] = GetMessage(
						"BLG_GP_PUSH_8A".$aditGM,
						array(
							"#name#" => htmlspecialcharsbx($authorName),
							"#post#" => GetMessage("BLG_GP_IM_8B")
						)
					);
				}
			}
			elseif($arParams["TYPE"] == "SHARE2USERS")
			{
				$arMessageFields["PUSH_PARAMS"] = array(
					"ACTION" => "share2users"
				);

				if (!empty($authorAvatarUrl))
				{
					$arMessageFields["PUSH_PARAMS"]["ADVANCED_PARAMS"] = array(
						'avatarUrl' => $authorAvatarUrl,
						'senderName' => $authorName
					);
				}

				$arMessageFields["NOTIFY_EVENT"] = "share2users";
				$arMessageFields["NOTIFY_TAG"] = "BLOG|SHARE2USERS|".$arParams["ID"];
				if (!$bTitleEmpty)
				{
					$arMessageFields["NOTIFY_MESSAGE"] = GetMessage(
						"BLG_GP_IM_9".$aditGM,
						array(
							"#title#" => "<a href=\"".$arParams["URL"]."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arParams["TITLE"])."</a>"
						)
					);
					$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage(
							"BLG_GP_IM_9".$aditGM,
							array(
								"#title#" => htmlspecialcharsbx($arParams["TITLE_OUT"])
							)
						)." ".$serverName.$arParams["URL"]."";
					$arMessageFields["PUSH_MESSAGE"] = GetMessage(
						"BLG_GP_PUSH_9".$aditGM,
						array(
							"#name#" => htmlspecialcharsbx($authorName),
							"#title#" => htmlspecialcharsbx($arParams["TITLE"])
						)
					);
				}
				else
				{
					$arMessageFields["NOTIFY_MESSAGE"] = GetMessage(
						"BLG_GP_IM_9A".$aditGM,
						array(
							"#post#" => "<a href=\"".$arParams["URL"]."\" class=\"bx-notifier-item-action\">".GetMessage("BLG_GP_IM_9B")."</a>"
						)
					);
					$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage(
							"BLG_GP_IM_9A".$aditGM,
							array(
								"#post#" => GetMessage("BLG_GP_IM_9B")
							)
						)." ".$serverName.$arParams["URL"]."";
					$arMessageFields["PUSH_MESSAGE"] = GetMessage(
						"BLG_GP_PUSH_9A".$aditGM,
						array(
							"#name#" => htmlspecialcharsbx($authorName),
							"#post#" => GetMessage("BLG_GP_IM_9B")
						)
					);
				}
			}

			$arMessageFields["PUSH_PARAMS"]["TAG"] = $arMessageFields["NOTIFY_TAG"];
		}

		foreach($arUsers as $v)
		{
			if(
				in_array($v, $arUserIDSent)
				|| (
					!empty($arParams["EXCLUDE_USERS"])
					&& IntVal($arParams["EXCLUDE_USERS"][$v]) > 0
				)
			)
			{
				continue;
			}

			if (IsModuleInstalled("extranet"))
			{
				$arTmp = CSocNetLogTools::ProcessPath(
					array(
						"URL" => $arParams["URL"],
					),
					$v,
					SITE_ID
				);
				$url = $arTmp["URLS"]["URL"];

				$serverName = (
				strpos($url, "http://") === 0
				|| strpos($url, "https://") === 0
					? ""
					: $arTmp["SERVER_NAME"]
				);

				if($arParams["TYPE"] == "POST")
				{
					if (!$bTitleEmpty)
					{
						$arMessageFields["NOTIFY_MESSAGE"] = GetMessage("BLG_GP_IM_1".$aditGM, Array("#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arParams["TITLE"])."</a>"));
						$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage("BLG_GP_IM_1".$aditGM, Array("#title#" => htmlspecialcharsbx($arParams["TITLE_OUT"])))." (".$serverName.$url.")";
					}
					else
					{
						$arMessageFields["NOTIFY_MESSAGE"] = GetMessage("BLG_GP_IM_1A".$aditGM, Array("#post#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".GetMessage("BLG_GP_IM_1B")."</a>"));
						$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage("BLG_GP_IM_1A".$aditGM, Array("#post#" => GetMessage("BLG_GP_IM_1B")))." (".$serverName.$url.")";
					}
				}
				elseif($arParams["TYPE"] == "COMMENT")
				{
					$commentCropped = truncateText($arParams["BODY"], 100);

					if (!$bTitleEmpty)
					{
						$arMessageFields["NOTIFY_MESSAGE"] = GetMessage("BLG_GP_IM_41".$aditGM, array(
							"#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arParams["TITLE"])."</a>",
							"#comment#" => $commentCropped
						));
						$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage("BLG_GP_IM_41".$aditGM, array(
								"#title#" => htmlspecialcharsbx($arParams["TITLE_OUT"]),
								"#comment#" => $arParams["BODY"]
							))." ".$serverName.$url;
						$arMessageFields["NOTIFY_MESSAGE_AUTHOR"] = GetMessage("BLG_GP_IM_51".$aditGM, array(
							"#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arParams["TITLE"])."</a>",
							"#comment#" => $commentCropped
						));
						$arMessageFields["NOTIFY_MESSAGE_AUTHOR_OUT"] = GetMessage("BLG_GP_IM_51".$aditGM, array(
								"#title#" => htmlspecialcharsbx($arParams["TITLE_OUT"]),
								"#comment#" => $arParams["BODY"]
							))." ".$serverName.$url;
					}
					else
					{
						$arMessageFields["NOTIFY_MESSAGE"] = GetMessage("BLG_GP_IM_41A".$aditGM, array(
							"#post#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".GetMessage("BLG_GP_IM_4B")."</a>",
							"#comment#" => $commentCropped
						));
						$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage("BLG_GP_IM_41A".$aditGM, array(
								"#post#" => GetMessage("BLG_GP_IM_4B"),
								"#comment#" => $arParams["BODY"]
							))." ".$serverName.$url;
						$arMessageFields["NOTIFY_MESSAGE_AUTHOR"] = GetMessage("BLG_GP_IM_51A".$aditGM, array(
							"#post#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".GetMessage("BLG_GP_IM_5B")."</a>",
							"#comment#" => $commentCropped
						));
						$arMessageFields["NOTIFY_MESSAGE_AUTHOR_OUT"] = GetMessage("BLG_GP_IM_51A".$aditGM, array(
								"#post#" => GetMessage("BLG_GP_IM_5B"),
								"#comment#" => $arParams["BODY"]
							))." ".$serverName.$url;
					}
				}
				elseif($arParams["TYPE"] == "SHARE")
				{
					if (!$bTitleEmpty)
					{
						$arMessageFields["NOTIFY_MESSAGE"] = GetMessage("BLG_GP_IM_8".$aditGM, Array("#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arParams["TITLE"])."</a>"));
						$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage("BLG_GP_IM_8".$aditGM, Array("#title#" => htmlspecialcharsbx($arParams["TITLE_OUT"])))." ".$serverName.$url."";
					}
					else
					{
						$arMessageFields["NOTIFY_MESSAGE"] = GetMessage("BLG_GP_IM_8A".$aditGM, Array("#post#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".GetMessage("BLG_GP_IM_8B")."</a>"));
						$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage("BLG_GP_IM_8A".$aditGM, Array("#post#" => GetMessage("BLG_GP_IM_8B")))." ".$serverName.$url."";
					}
				}
				elseif($arParams["TYPE"] == "SHARE2USERS")
				{
					if (!$bTitleEmpty)
					{
						$arMessageFields["NOTIFY_MESSAGE"] = GetMessage("BLG_GP_IM_9".$aditGM, Array("#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arParams["TITLE"])."</a>"));
						$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage("BLG_GP_IM_9".$aditGM, Array("#title#" => htmlspecialcharsbx($arParams["TITLE_OUT"])))." ".$serverName.$url."";
					}
					else
					{
						$arMessageFields["NOTIFY_MESSAGE"] = GetMessage("BLG_GP_IM_9A".$aditGM, Array("#post#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".GetMessage("BLG_GP_IM_9B")."</a>"));
						$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage("BLG_GP_IM_9A".$aditGM, Array("#post#" => GetMessage("BLG_GP_IM_9B")))." ".$serverName.$url."";
					}
				}
			}

			$arMessageFieldsTmp = $arMessageFields;
			if($arParams["TYPE"] == "COMMENT")
			{
				if($arParams["AUTHOR_ID"] == $v)
				{
					$arMessageFieldsTmp["NOTIFY_MESSAGE"] = $arMessageFields["NOTIFY_MESSAGE_AUTHOR"];
					$arMessageFieldsTmp["NOTIFY_MESSAGE_OUT"] = $arMessageFields["NOTIFY_MESSAGE_AUTHOR_OUT"];
					$arMessageFieldsTmp["PUSH_MESSAGE"] = $arMessageFields["PUSH_MESSAGE_AUTHOR"];
				}
			}

			$arMessageFieldsTmp["TO_USER_ID"] = $v;

			CIMNotify::Add($arMessageFieldsTmp);

			$arUserIDSent[] = $v;
		}

		// notify sonet groups subscribers
		if (
			$arParams["TYPE"] == "POST"
			&& !empty($arParams["TO_SOCNET_RIGHTS"])
		)
		{
			$arGroupsId = array();
			foreach($arParams["TO_SOCNET_RIGHTS"] as $perm_tmp)
			{
				if (
					preg_match('/^SG(\d+)_'.SONET_ROLES_USER.'$/', $perm_tmp, $matches)
					|| preg_match('/^SG(\d+)$/', $perm_tmp, $matches)
				)
				{
					$group_id_tmp = $matches[1];
					if (
						$group_id_tmp > 0 
						&& (
							!array_key_exists("SG", $arParams["TO_SOCNET_RIGHTS_OLD"]) 
							|| empty($arParams["TO_SOCNET_RIGHTS_OLD"]["SG"][$group_id_tmp])
						)
					)
					{
						$arGroupsId[] = $group_id_tmp;
					}
				}
			}

			if (!empty($arGroupsId))
			{
				$arTitle = self::processNotifyTitle($arParams["TITLE"]);
				$title = $arTitle['TITLE'];
				$title_out = $arTitle['TITLE_OUT'];

				$arNotifyParams = array(
					"LOG_ID" => $arMessageFields["LOG_ID"],
					"GROUP_ID" => $arGroupsId,
					"NOTIFY_MESSAGE" => "",
					"FROM_USER_ID" => $arParams["FROM_USER_ID"],
					"URL" => $arParams["URL"],
					"MESSAGE" => GetMessage("SONET_IM_NEW_POST", Array(
						"#title#" => "[URL=#URL#]".$title."[/URL]",
					)),
					"MESSAGE_CHAT" => GetMessage("SONET_IM_NEW_POST_CHAT".$aditGM, Array(
						"#title#" => "[URL=#URL#]".$title_out."[/URL]",
					)),
					"MESSAGE_OUT" => GetMessage("SONET_IM_NEW_POST", Array(
						"#title#" => $title_out
					))." #URL#",
					"EXCLUDE_USERS" => array_merge(array($arParams["FROM_USER_ID"]), array($arUserIDSent))
				);

				$arUserIDSentBySubscription = CSocNetSubscription::NotifyGroup($arNotifyParams);
				if (!$arUserIDSentBySubscription)
				{
					$arUserIDSentBySubscription = array();
				}
				$arUserIDSent = array_merge($arUserIDSent, $arUserIDSentBySubscription);
			}
		}

		return $arUserIDSent;
	}

	public static function NotifyImReady($arParams = array())
	{
		$arUserIDSent = array();
		$moderatorList = array();

		if (
			!\Bitrix\Main\Loader::includeModule("im")
			|| !\Bitrix\Main\Loader::includeModule("socialnetwork")
		)
		{
			return $arUserIDSent;
		}

		if (!in_array($arParams['TYPE'], array('POST', 'COMMENT')))
		{
			return $arUserIDSent;
		}

		if (
			isset($arParams["TO_SOCNET_RIGHTS"])
			&& is_array($arParams["TO_SOCNET_RIGHTS"])
			&& !empty($arParams["TO_SOCNET_RIGHTS"])
		)
		{
			$arGroupChecked = array();
			foreach($arParams["TO_SOCNET_RIGHTS"] as $code)
			{

				if (preg_match('/^SG(\d+)/', $code, $matches))
				{
					$sonetGroupId = intval($matches[1]);

					if (in_array($sonetGroupId, $arGroupChecked))
					{
						break;
					}
					else
					{
						$arGroupChecked[] = $sonetGroupId;
					}

					if ($sonetGroupId > 0)
					{
						$featureOperationPerms = CSocNetFeaturesPerms::GetOperationPerm(
							SONET_ENTITY_GROUP,
							$sonetGroupId,
							'blog',
							($arParams['TYPE'] == 'POST' ? 'moderate_post' : 'moderate_comment')
						);

						if ($featureOperationPerms)
						{
							$res = \Bitrix\Socialnetwork\UserToGroupTable::getList(array(
								'filter' => array(
									'<=ROLE' => $featureOperationPerms,
									'GROUP_ID' => $sonetGroupId,
									'=GROUP.ACTIVE' => 'Y'
								),
								'select' => array('USER_ID')
							));
							while ($relation = $res->fetch())
							{
								if (!isset($moderatorList[$relation['USER_ID']]))
								{
									$moderatorList[$relation['USER_ID']] = array(
										'USER_ID' => $relation['USER_ID'],
										'GROUP_ID' => $sonetGroupId
									);
								}
							}
						}
					}
				}
			}
		}

		if (!empty($moderatorList))
		{
			$arMessageFields = array(
				"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
				"NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
				"NOTIFY_MODULE" => "blog",
			);

			$arTitle = self::processNotifyTitle($arParams["TITLE"]);
			$arParams["TITLE"] = $arTitle['TITLE'];
			$arParams["TITLE_OUT"] = $arTitle['TITLE_OUT'];
			$bTitleEmpty = $arTitle['IS_TITLE_EMPTY'];
			$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0) ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));
			$moderationUrl = \Bitrix\Main\Config\Option::get('socialnetwork', 'workgroups_page', SITE_DIR.'workgroups/').'group/#group_id#/blog/moderation/';

			if ($arParams["TYPE"] == "POST")
			{
				$arMessageFields["NOTIFY_EVENT"] = "moderate_post";
				$arMessageFields["NOTIFY_TAG"] = "BLOG|MODERATE_POST|".$arParams["POST_ID"];

				$arMessageFields["NOTIFY_MESSAGE"] = GetMessage(
					(!$bTitleEmpty ? "SONET_IM_NEW_POST_TO_MODERATE_W_TITLE" : "SONET_IM_NEW_POST_TO_MODERATE_WO_TITLE"),
					array(
						"#link_mod_start#" => "<a href=\"#MODERATION_URL#\" class=\"bx-notifier-item-action\">",
						"#link_mod_end#" => "</a>",
						"#title#" => htmlspecialcharsbx($arParams["TITLE"])
					)
				);

				$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage(
					(!$bTitleEmpty ? "SONET_IM_NEW_POST_TO_MODERATE_W_TITLE" : "SONET_IM_NEW_POST_TO_MODERATE_WO_TITLE"),
					array(
						"#link_mod_start#" => "",
						"#link_mod_end#" => "",
						"#title#" => htmlspecialcharsbx($arParams["TITLE_OUT"])
					)
				)." #SERVER_NAME##MODERATION_URL#";
			}
			else
			{
				$arMessageFields["NOTIFY_EVENT"] = "moderate_comment";
				$arMessageFields["NOTIFY_TAG"] = "BLOG|COMMENT|".$arParams["POST_ID"].'|'.$arParams["COMMENT_ID"];

				$arMessageFields["NOTIFY_MESSAGE"] = GetMessage(
					(!$bTitleEmpty ? "SONET_IM_NEW_COMMENT_TO_MODERATE_W_TITLE" : "SONET_IM_NEW_COMMENT_TO_MODERATE_WO_TITLE"),
					array(
						"#link_com_start#" => "<a href=\"#COMMENT_URL#\" class=\"bx-notifier-item-action\">",
						"#link_com_end#" => "</a>",
						"#title#" => htmlspecialcharsbx($arParams["TITLE"])
					)
				);

				$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage(
						(!$bTitleEmpty ? "SONET_IM_NEW_COMMENT_TO_MODERATE_W_TITLE" : "SONET_IM_NEW_COMMENT_TO_MODERATE_WO_TITLE"),
						array(
							"#link_com_start#" => "",
							"#link_com_end#" => "",
							"#title#" => htmlspecialcharsbx($arParams["TITLE_OUT"])
						)
					)." #SERVER_NAME##COMMENT_URL#";
			}

			foreach($moderatorList as $moderator)
			{
				$moderatorId = $moderator['USER_ID'];
				$groupId = $moderator['GROUP_ID'];

				if ($moderatorId != $arParams["FROM_USER_ID"])
				{
					$arMessageFieldsCurrent = $arMessageFields;
					$arMessageFieldsCurrent["TO_USER_ID"] = $moderatorId;

					$userModerationUrl = str_replace('#group_id#', $groupId, $moderationUrl);
					$userCommentUrl = $arParams['COMMENT_URL'];

					if (IsModuleInstalled("extranet"))
					{
						$arTmp = CSocNetLogTools::ProcessPath(
							array(
								"MODERATION_URL" => $userModerationUrl,
								"COMMENT_URL" => (isset($arParams['COMMENT_URL']) ? $arParams['COMMENT_URL'] : '')
							),
							$moderatorId,
							SITE_ID
						);

						$userModerationUrl = $arTmp["URLS"]["MODERATION_URL"];
						$userCommentUrl = $arTmp["URLS"]["COMMENT_URL"];

						$serverName = (
							strpos($userModerationUrl, "http://") === 0
							|| strpos($userModerationUrl, "https://") === 0
								? ""
								: $arTmp["SERVER_NAME"]
						);
					}

					$arMessageFieldsCurrent["NOTIFY_MESSAGE"] = str_replace(
						array('#MODERATION_URL#', '#COMMENT_URL#'),
						array($userModerationUrl, $userCommentUrl),
						$arMessageFields["NOTIFY_MESSAGE"]
					);
					$arMessageFieldsCurrent["NOTIFY_MESSAGE_OUT"] = str_replace(
						array('#MODERATION_URL#', '#SERVER_NAME#', '#COMMENT_URL#'),
						array($userModerationUrl, $serverName, $userCommentUrl),
						$arMessageFields["NOTIFY_MESSAGE_OUT"]
					);

					CIMNotify::Add($arMessageFieldsCurrent);

					$arUserIDSent[] = $moderatorId;
				}
			}
		}

		return $arUserIDSent;
	}

	public static function NotifyImPublish($arParams = array())
	{
		if (
			!\Bitrix\Main\Loader::includeModule("im")
			|| !\Bitrix\Main\Loader::includeModule("socialnetwork")
		)
		{
			return false;
		}

		if (!in_array($arParams['TYPE'], array('POST', 'COMMENT')))
		{
			return false;
		}

		$arTitle = self::processNotifyTitle($arParams["TITLE"]);
		$arParams["TITLE"] = $arTitle['TITLE'];
		$arParams["TITLE_OUT"] = $arTitle['TITLE_OUT'];
		$bTitleEmpty = $arTitle['IS_TITLE_EMPTY'];
		$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0) ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));

		$arMessageFields = array(
			"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
			"NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
			"NOTIFY_MODULE" => "blog",
			"TO_USER_ID" => $arParams["TO_USER_ID"]
		);

		if ($arParams["TYPE"] == "POST")
		{
			$arMessageFields["NOTIFY_EVENT"] = "published_post";
			$arMessageFields["NOTIFY_TAG"] = "BLOG|POST|".$arParams["POST_ID"];

			$arMessageFields["NOTIFY_MESSAGE"] = GetMessage(
				(!$bTitleEmpty ? "SONET_IM_NEW_POST_PUBLISHED_W_TITLE" : "SONET_IM_NEW_POST_PUBLISHED_WO_TITLE"),
				array(
					"#link_post_start#" => "<a href=\"#POST_URL#\" class=\"bx-notifier-item-action\">",
					"#link_post_end#" => "</a>",
					"#title#" => htmlspecialcharsbx($arParams["TITLE"])
				)
			);

			$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage(
					(!$bTitleEmpty ? "SONET_IM_NEW_POST_PUBLISHED_W_TITLE" : "SONET_IM_NEW_POST_PUBLISHED_WO_TITLE"),
					array(
						"#link_post_start#" => "",
						"#link_post_end#" => "",
						"#title#" => htmlspecialcharsbx($arParams["TITLE_OUT"])
					)
				)." #SERVER_NAME##POST_URL#";
		}
		else
		{
			$arMessageFields["NOTIFY_EVENT"] = "published_comment";
			$arMessageFields["NOTIFY_TAG"] = "BLOG|COMMENT|".$arParams["POST_ID"]."|".$arParams["COMMENT_ID"];

			$arMessageFields["NOTIFY_MESSAGE"] = GetMessage(
				(!$bTitleEmpty ? "SONET_IM_NEW_COMMENT_PUBLISHED_W_TITLE" : "SONET_IM_NEW_COMMENT_PUBLISHED_WO_TITLE"),
				array(
					"#link_com_start#" => "<a href=\"#COMMENT_URL#\" class=\"bx-notifier-item-action\">",
					"#link_com_end#" => "</a>",
					"#title#" => htmlspecialcharsbx($arParams["TITLE"])
				)
			);

			$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage(
					(!$bTitleEmpty ? "SONET_IM_NEW_COMMENT_PUBLISHED_W_TITLE" : "SONET_IM_NEW_COMMENT_PUBLISHED_WO_TITLE"),
					array(
						"#link_com_start#" => "",
						"#link_com_end#" => "",
						"#title#" => htmlspecialcharsbx($arParams["TITLE_OUT"])
					)
				)." #SERVER_NAME##COMMENT_URL#";
		}

		$userPostUrl = (isset($arParams['POST_URL']) ? $arParams['POST_URL'] : '');
		$userCommentUrl = (isset($arParams['POST_URL']) ? $arParams['COMMENT_URL'] : '');

		if (IsModuleInstalled("extranet"))
		{
			$arTmp = CSocNetLogTools::ProcessPath(
				array(
					"POST_URL" => $userPostUrl,
					"COMMENT_URL" => $userCommentUrl
				),
				$arParams["TO_USER_ID"],
				SITE_ID
			);

			$userPostUrl = $arTmp["URLS"]["POST_URL"];
			$userCommentUrl = $arTmp["URLS"]["COMMENT_URL"];

			$serverName = (
				strpos($userPostUrl, "http://") === 0
				|| strpos($userPostUrl, "https://") === 0
					? ""
					: $arTmp["SERVER_NAME"]
			);
		}

		$arMessageFields["NOTIFY_MESSAGE"] = str_replace(
			array('#POST_URL#', '#COMMENT_URL#'),
			array($userPostUrl, $userCommentUrl),
			$arMessageFields["NOTIFY_MESSAGE"]
		);
		$arMessageFields["NOTIFY_MESSAGE_OUT"] = str_replace(
			array('#POST_URL#', '#SERVER_NAME#', '#COMMENT_URL#'),
			array($userPostUrl, $serverName, $userCommentUrl),
			$arMessageFields["NOTIFY_MESSAGE_OUT"]
		);

		CIMNotify::Add($arMessageFields);

		return true;
	}


	private static function processNotifyTitle($title)
	{
		$title = str_replace(array("\r\n", "\n"), " ", $title);

		return array(
			'TITLE' => truncateText($title, 100),
			'TITLE_OUT' => truncateText($title, 255),
			'IS_TITLE_EMPTY' => (strlen(trim($title, " \t\n\r\0\x0B\xA0" )) <= 0)
		);
	}

	public static function NotifyMail($arFields)
	{
		if (!CModule::IncludeModule('mail'))
		{
			return false;
		}

		if (
			!isset($arFields["postId"])
			|| intval($arFields["postId"]) <= 0
			|| !isset($arFields["userId"])
			|| !isset($arFields["postUrl"])
			|| strlen($arFields["postUrl"]) <= 0
		)
		{
			return false;
		}

		if (!is_array($arFields["userId"]))
		{
			$arFields["userId"] = array($arFields["userId"]);
		}

		if (!isset($arFields["siteId"]))
		{
			$arFields["siteId"] = SITE_ID;
		}

		$nameTemplate = CSite::GetNameFormat("", $arFields["siteId"]);
		$authorName = "";

		if (!empty($arFields["authorId"]))
		{
			$rsAuthor = CUser::GetById($arFields["authorId"]);
			$arAuthor = $rsAuthor->Fetch();
			$authorName = CUser::FormatName(
				$nameTemplate,
				$arAuthor,
				true,
				false
			);

			if (check_email($authorName))
			{
				$authorName = '"'.$authorName.'"';
			}

			foreach($arFields["userId"] as $key => $val)
			{
				if (intval($val) == intval($arFields["authorId"]))
				{
					unset($arFields["userId"][$key]);
				}
			}
		}

		if (empty($arFields["userId"]))
		{
			return false;
		}

		if (
			!isset($arFields["type"])
			|| !in_array(strtoupper($arFields["type"]), array("POST", "POST_SHARE", "COMMENT"))
		)
		{
			$arFields["type"] = "COMMENT";
		}

		$arEmail = \Bitrix\Mail\User::getUserData($arFields["userId"], $nameTemplate);
		if (empty($arEmail))
		{
			return false;
		}

		$arBlogPost = CBlogPost::GetByID(intval($arFields["postId"]));
		if (!$arBlogPost)
		{
			return false;
		}

		$arTitle = self::processNotifyTitle($arBlogPost["TITLE"]);
		$postTitle = $arTitle['TITLE'];

		switch (strtoupper($arFields["type"]))
		{
			case "COMMENT":
				$mailMessageId = "<BLOG_COMMENT_".$arFields["commentId"]."@".$GLOBALS["SERVER_NAME"].">";
				$mailTemplateType = "BLOG_SONET_NEW_COMMENT";
				break;
			case "POST_SHARE":
				$mailMessageId = "<BLOG_POST_".$arFields["postId"]."@".$GLOBALS["SERVER_NAME"].">";
				$mailTemplateType = "BLOG_SONET_POST_SHARE";
				break;
			default:
				$mailMessageId = "<BLOG_POST_".$arFields["postId"]."@".$GLOBALS["SERVER_NAME"].">";
				$mailTemplateType = "BLOG_SONET_NEW_POST";
		}

		$mailMessageInReplyTo = "<BLOG_POST_".$arFields["postId"]."@".$GLOBALS["SERVER_NAME"].">";
		$defaultEmailFrom = \Bitrix\Mail\User::getDefaultEmailFrom();

		foreach ($arEmail as $userId => $arUser)
		{
			$email = $arUser["EMAIL"];
			$nameFormatted = str_replace(array('<', '>', '"'), '', $arUser["NAME_FORMATTED"]);

			if (
				intval($userId) <= 0
				&& strlen($email) <= 0
			)
			{
				continue;
			}

			$res = \Bitrix\Mail\User::getReplyTo(
				$arFields["siteId"],
				$userId,
				'BLOG_POST',
				$arFields["postId"],
				$arFields["postUrl"]
			);
			if (is_array($res))
			{
				list($replyTo, $backUrl) = $res;

				if (
					$replyTo
					&& $backUrl
				)
				{
					$authorName = str_replace(array('<', '>', '"'), '', $authorName);
					CEvent::Send(
						$mailTemplateType,
						$arFields["siteId"],
						array(
							"=Reply-To" => $authorName.' <'.$replyTo.'>',
							"=Message-Id" => $mailMessageId,
							"=In-Reply-To" => $mailMessageInReplyTo,
							"EMAIL_FROM" => $authorName.' <'.$defaultEmailFrom.'>',
							"EMAIL_TO" => (!empty($nameFormatted) ? ''.$nameFormatted.' <'.$email.'>' : $email),
							"RECIPIENT_ID" => $userId,
							"COMMENT_ID" => (isset($arFields["commentId"]) ? intval($arFields["commentId"]) : false),
							"POST_ID" => intval($arFields["postId"]),
							"POST_TITLE" => $postTitle,
							"URL" => $arFields["postUrl"]
						)
					);
				}
			}
		}

		if (
			strtoupper($arFields["type"]) == 'COMMENT'
			&& \Bitrix\Main\Loader::includeModule('crm')
		)
		{
			CCrmLiveFeedComponent::processCrmBlogComment(array(
				"AUTHOR" => isset($arAuthor) ? $arAuthor : false,
				"POST_ID" => intval($arFields["postId"]),
				"COMMENT_ID" => intval($arFields["commentId"]),
				"USER_ID" => array_keys($arEmail)
			));
		}

		return true;
	}

	function DeleteSocNetPostPerms($postId)
	{
		global $DB;
		$postId = IntVal($postId);
		if($postId <= 0)
			return;

		$DB->Query("DELETE FROM b_blog_socnet_rights WHERE POST_ID = ".$postId, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	public static function GetMentionedUserID($arFields)
	{
		global $USER_FIELD_MANAGER;
		$arMentionedUserID = array();

		if (isset($arFields["DETAIL_TEXT"]))
		{
			preg_match_all("/\[user\s*=\s*([^\]]*)\](.+?)\[\/user\]/is".BX_UTF_PCRE_MODIFIER, $arFields["DETAIL_TEXT"], $arMention);
			if (!empty($arMention))
			{
				$arMentionedUserID = array_merge($arMentionedUserID, $arMention[1]);
			}
		}

		$arPostUF = $USER_FIELD_MANAGER->GetUserFields("BLOG_POST", $arFields["ID"], LANGUAGE_ID);

		if (
			is_array($arPostUF)
			&& isset($arPostUF["UF_GRATITUDE"])
			&& is_array($arPostUF["UF_GRATITUDE"])
			&& isset($arPostUF["UF_GRATITUDE"]["VALUE"])
			&& intval($arPostUF["UF_GRATITUDE"]["VALUE"]) > 0
			&& CModule::IncludeModule("iblock")
		)
		{
			if (
				!is_array($GLOBALS["CACHE_HONOUR"])
				|| !array_key_exists("honour_iblock_id", $GLOBALS["CACHE_HONOUR"])
				|| intval($GLOBALS["CACHE_HONOUR"]["honour_iblock_id"]) <= 0
			)
			{
				$rsIBlock = CIBlock::GetList(array(), array("=CODE" => "honour", "=TYPE" => "structure"));
				if ($arIBlock = $rsIBlock->Fetch())
				{
					$GLOBALS["CACHE_HONOUR"]["honour_iblock_id"] = $arIBlock["ID"];
				}
			}

			if (intval($GLOBALS["CACHE_HONOUR"]["honour_iblock_id"]) > 0)
			{
				$rsElementProperty = CIBlockElement::GetProperty(
					$GLOBALS["CACHE_HONOUR"]["honour_iblock_id"],
					$arPostUF["UF_GRATITUDE"]["VALUE"]
				);
				while ($arElementProperty = $rsElementProperty->GetNext())
				{
					if (
						$arElementProperty["CODE"] == "USERS"
						&& intval($arElementProperty["VALUE"]) > 0
					)
					{
						$arMentionedUserID[] = $arElementProperty["VALUE"];
					}
				}
			}
		}

		return $arMentionedUserID;
	}

}
?>

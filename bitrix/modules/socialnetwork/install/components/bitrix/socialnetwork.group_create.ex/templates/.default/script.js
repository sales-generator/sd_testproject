function BXOnInviteListChange()
{
	window.arInvitationUsersList = arguments[0];
	BX.onCustomEvent('onInvitationUsersListChange', [BX.util.array_values(window.arInvitationUsersList)]);
}

function BXSwitchExtranet(isChecked)
{
	if (BX("INVITE_EXTRANET_block"))
	{
		BX("INVITE_EXTRANET_block").style.display = (isChecked ? "block" : "none");
	}

	if (BX("GROUP_OPENED_block") && BX("GROUP_OPENED"))
	{
		if (isChecked)
		{
			BX("GROUP_OPENED").checked = false;
			BX("GROUP_OPENED").disabled = true;
			BX.addClass(BX('GROUP_OPENED_block'), 'sonet-group-create-popup-checkbox-disabled');
		}
		else
		{
			BX("GROUP_OPENED").disabled = true;
			BX("GROUP_OPENED_block").style.display = "block";
			BX.addClass(BX('GROUP_OPENED_block'), 'sonet-group-create-popup-checkbox-disabled');
		}
	}

	if (BX("GROUP_VISIBLE_block") && BX("GROUP_VISIBLE"))
	{
		if (isChecked)
		{
			BX("GROUP_VISIBLE").checked = false;
			BX("GROUP_VISIBLE").disabled = true;
			BX.addClass(BX('GROUP_VISIBLE_block'), 'sonet-group-create-popup-checkbox-disabled');
		}
		else
		{
			BX("GROUP_VISIBLE").disabled = false;
			BX("GROUP_VISIBLE_block").style.display = "block";
			BX.removeClass(BX('GROUP_VISIBLE_block'), 'sonet-group-create-popup-checkbox-disabled');
		}
	}

	if (BX("GROUP_INITIATE_PERMS") && BX("GROUP_INITIATE_PERMS_OPTION_E") && BX("GROUP_INITIATE_PERMS_OPTION_K"))
	{
		if (isChecked)
		{
			BX("GROUP_INITIATE_PERMS_OPTION_E").selected = true;
		}
		else
		{
			BX("GROUP_INITIATE_PERMS_OPTION_K").selected = true;
		}
	}
	
	if (BX("USERS_employee_section_extranet"))
	{
		BX("USERS_employee_section_extranet").style.display = (isChecked ? "inline-block" : "none");
	}

	if (BX("UF_SG_DEPT_block"))
	{
		BX("UF_SG_DEPT_block").style.display = (isChecked ? "none" : "block");
	}
}

function BXSwitchNotVisible(isChecked)
{
	if (isChecked)
	{
		BX("GROUP_OPENED").disabled = false;
		BX.removeClass(BX('GROUP_OPENED_block'), 'sonet-group-create-popup-checkbox-disabled');
	}
	else
	{
		BX("GROUP_OPENED").disabled = true;
		BX("GROUP_OPENED").checked = false;
		BX.addClass(BX('GROUP_OPENED_block'), 'sonet-group-create-popup-checkbox-disabled');
	}
}

function BXDeleteImage()
{
	if (BX("sonet_group_create_tabs_image_block") && BX("GROUP_IMAGE_ID_DEL"))
	{
		BX("sonet_group_create_tabs_image_block").style.visibility = "hidden";
		BX("GROUP_IMAGE_ID_DEL").value = "Y";
		if (BX("file_input_GROUP_IMAGE_ID"))
			BX("file_input_GROUP_IMAGE_ID").value = "";
		if (BX("file_input_upload_list_GROUP_IMAGE_ID"))
		{
			var tmpNode = BX.findChild(BX("file_input_upload_list_GROUP_IMAGE_ID"), { tagName: 'input', attr: { name: 'GROUP_IMAGE_ID' } }, true, false);
			if (tmpNode)
				tmpNode.value = "";
		}
	}
}

function BXGCESwitchTabs()
{
	var tabs = BX.findChildren(BX("sonet_group_create_popup"), { className: "popup-window-tab" }, true);
	var blockList = BX.findChildren(BX("sonet_group_create_tabs_content"), { tagName: "div" }, false);

	BX.bind(BX.findChild(BX("sonet_group_create_popup"), { className: "popup-window-tabs" }, true, false), "click", function(event) {
		event = event || window.event;
		var target = event.target || event.srcElement;
		var blockOld = null;
		var blockNew = null;
		var i = 0;

		for(i=0; i<blockList.length; i++)
		{
			if (blockList[i].style.display != 'none')
			{
				blockOld = blockList[i];
				var posOld = BX.pos(blockOld);
				var tabsContainer = BX('sonet_group_create_tabs_content');
				tabsContainer.style.height = posOld['height'] + 'px';
				tabsContainer.style.overflow = 'hidden';
				break;
			}
		}

		if (
			BX.hasClass(BX(target), 'popup-window-tab')
			|| BX.hasClass(BX(target.parentNode), 'popup-window-tab')
		)
		{
			for(i=0; i<tabs.length; i++)
			{
				BX.removeClass(tabs[i], "popup-window-tab-selected");
				blockList[i].style.display = "none";
				if (
					tabs[i] == target 
					|| tabs[i] == target.parentNode
				)
				{
					BX.addClass(tabs[i], "popup-window-tab-selected");
					blockNew = blockList[i];
				}
			}

			if (
				blockOld 
				&& blockNew
				&& tabsContainer
			)
			{
				if (blockOld.id != blockNew.id)
				{
					blockNew.style.display = 'block';
					var posNew = BX.pos(blockNew);

					new BX.easing({
						start: { height: posOld['height'] },
						finish: { height: posNew['height'] },
						duration: 400,
						transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),

						step: function (state) {
							this.style.height = state.height + 'px';
						}.bind(tabsContainer),

						complete: function () {
							this.style.height = 'auto';
							this.style.overflow = 'visible';
						}.bind(tabsContainer)
					}).animate();
				}
				else
				{
					blockNew.style.display = 'block';
					tabsContainer.style.overflow = 'visible';
				}
			}
		}
	})
}

function BXGCESwitchFeatures(){
	var servBlock = BX("sonet_group_create_tabs_features");
	if (servBlock)
	{
		var servList = BX.findChildren(servBlock, { className: "sonet-group-create-popup-feature"}, true);
		var inputList = BX.findChildren(servBlock, { className: "sonet-group-create-popup-feature-hidden"}, true);

		BX.bind(servBlock, "click", function(event){
			event = event || window.event;
			var target = event.target || event.srcElement;
			for(var i=0; i<servList.length; i++){
				if(target == servList[i] || target.parentNode == servList[i]){
					BX.toggleClass(servList[i], 'sonet-group-create-popup-feature-active');
					if (BX.hasClass(servList[i], "sonet-group-create-popup-feature-active"))
						inputList[i].value = "Y";
					else
						inputList[i].value = "";
					break;
				}
			}
		});
	}
}

function BXGCESubmitForm(e)
{
	var textarea = BX("GROUP_DESCRIPTION");
	if (
		textarea 
		&& textarea.value == BX.message("SONET_GCE_T_DESCR")
	)
	{
		textarea.value = '';
	}
	
	if (BX('EXTRANET_INVITE_ACTION'))
	{
		BX('EXTRANET_INVITE_ACTION').value = BX.BXGCE.lastAction;
	}

	var actionURL = BX("sonet_group_create_popup_form").action;

	if (actionURL)
	{
		BX.BXGCE.disableSubmitButton(true);

		BX.ajax.submitAjax(
			document.forms.sonet_group_create_popup_form,
			{
				url: actionURL,
				method: 'POST',
				dataType: 'json',
				onsuccess: function(obResponsedata)
				{
					if (
						obResponsedata["ERROR"] !== undefined
						&& obResponsedata["ERROR"].length > 0
					)
					{
						BX.BXGCE.showError((obResponsedata["WARNING"] !== undefined && obResponsedata["WARNING"].length > 0 ? obResponsedata["WARNING"] + '<br>' : '') + obResponsedata["ERROR"]);

						if (
							typeof BX.SocNetLogDestination.obItems !== 'undefined'
							&& obResponsedata["USERS_ID"] !== undefined
							&& BX.type.isArray(obResponsedata["USERS_ID"])
						)
						{
							var selectedUsersOld = false;
							var selectedUsers = [];
							var strUserCodeTmp = false;
							var j = 0;

							for (j = 0; j < obResponsedata["USERS_ID"].length; j++)
							{
								selectedUsers['U' + obResponsedata['USERS_ID'][j]] = 'users';
							}

							if (BX.BXGCE.arUserSelector.length > 0)
							{
								for (var i = 0; i < BX.BXGCE.arUserSelector.length; i++)
								{
									selectedUsersOld = BX.findChildren(BX('sonet_group_create_popup_users_item_post_' + BX.BXGCE.arUserSelector[i]), { className: "feed-add-post-destination-users" }, true);
									if (selectedUsersOld)
									{
										for (j = 0; j < selectedUsersOld.length; j++)
										{
											strUserCodeTmp = selectedUsersOld[j].getAttribute('data-id');
											if (
												strUserCodeTmp
												&& strUserCodeTmp.length > 0
											)
											{
												BX.SocNetLogDestination.deleteItem(strUserCodeTmp, 'users', BX.BXGCE.arUserSelector[i]);
											}
										}
									}

									BX.SocNetLogDestination.obItemsSelected[BX.BXGCE.arUserSelector[i]] = selectedUsers;
									BX.SocNetLogDestination.reInit(BX.BXGCE.arUserSelector[i]);
								}
							}
						}

						BX.BXGCE.disableSubmitButton(false);
					}
					else if (obResponsedata["MESSAGE"] == 'SUCCESS')
					{
						top.BX.onCustomEvent('onSonetIframeSuccess');
						if (
							typeof obResponsedata["URL"] !== 'undefined'
							&& obResponsedata["URL"].length > 0
						)
						{
							top.location.href = obResponsedata["URL"];
						}
						else
						{
							BX.reload();
						}
					}
				},
				onfailure: function() {
					BX.BXGCE.disableSubmitButton(false);
					BX.BXGCE.showError(BX.message('SONET_GCE_T_AJAX_ERROR'));
				}
			}
		);
	}

	BX.PreventDefault(e);
}

function onCancelClick(e)
{
	top.BX.onCustomEvent('onSonetIframeCancelClick');
	return BX.PreventDefault(e);
}

function __addExtranetEmail(){

	var inputMail = BX('sonet_group_create_popup_form_email_input');

	if(inputMail.value == 'e-mail' || inputMail.value == '')
		return;

	var emailPattern = /^[a-zA-Z0-9._\-+~'=]+@[a-zA-Z0-9._-]+\.[a-zA-Z]{2,9}$/;

	if(emailPattern.test(inputMail.value))
	{
		if(top.BXExtranetMailList.length > 0)
		{
			for(var i=0; i < top.BXExtranetMailList.length; i++)
			{
				if(top.BXExtranetMailList[i] == inputMail.value)
				{
					BX('sonet_group_create_popup_form_email_' + (i + 1)).style.background = 'none';
					setTimeout(function(){BX('sonet_group_create_popup_form_email_'+(i+1)).style.backgroundColor = '#E1E9F6'}, 150);
					setTimeout(function(){BX('sonet_group_create_popup_form_email_'+(i+1)).style.background = 'none'}, 300);
					setTimeout(function(){BX('sonet_group_create_popup_form_email_'+(i+1)).style.backgroundColor = '#E1E9F6'}, 450);
					return;
				}
			}
		}

		var link = BX.create('a', {
			props:{
				className: 'sonet-group-create-popup-form-email',
				id: 'sonet_group_create_popup_form_email_' + (top.BXExtranetMailList.length + 1),
				href: 'javascript:void(0)'
			},
			children: [
					BX('sonet_group_create_popup_form_email_input').value,
					BX.create('a', {
						props:{
							className: 'sonet-group-create-popup-del',
							href: 'javascript:void(0)'
						},
						events: { click: __deleteExtranetEmail }
					})
			]
		});

		BX('sonet_group_create_popup_form_email_bl').appendChild(link);
		if (BX('EMAILS').value.length > 0)
			BX('EMAILS').value += ', ';
		BX('EMAILS').value += BX('sonet_group_create_popup_form_email_input').value;

		BX.removeClass(inputMail, 'sonet-group-create-popup-form-email-error');
		inputMail.value = '';

		top.BXExtranetMailList.push(inputMail.value);

	}
	else
	{
		if(BX.browser.IsIE())
		{
			inputMail.focus();
			inputMail.value = inputMail.value;
		}
		inputMail.focus();
		BX.addClass(inputMail, 'sonet-group-create-popup-form-email-error')
	}
}

function __deleteExtranetEmail(item)
{
	var flag = false;

	if (!item || !BX.type.isDomNode(item))
		item = this;

	if (item)
	{
		BX(item).parentNode.parentNode.removeChild(BX(item).parentNode);
		var num = parseInt(BX(item).parentNode.id.substring(36));
		top.BXExtranetMailList[num-1] = '';

		BX('EMAILS').value = '';
		for(var i=0; i<top.BXExtranetMailList.length; i++)
		{
			if (top.BXExtranetMailList[i].length > 0)
			{
				if (flag)
					BX('EMAILS').value += ', ';

				BX('EMAILS').value += top.BXExtranetMailList[i];
				flag = true;
			}
		}
	}
}

function BXGCEEmailKeyDown(event)
{
	event = event || window.event;
	BX.removeClass(this, 'sonet-group-create-popup-form-email-error');
	if(event.keyCode == 13)
		__addExtranetEmail();
}

(function(){

if (!!BX.BXGCE)
{
	return;
}

BX.BXGCE =
{
	userSelector: '',
	lastAction: 'invite',
	arUserSelector: []
};

BX.BXGCE.setSelector = function(selectorName)
{
	BX.BXGCE.userSelector = selectorName;
};

BX.BXGCE.disableBackspace = function(event)
{
	if (
		BX.SocNetLogDestination.backspaceDisable 
		|| BX.SocNetLogDestination.backspaceDisable != null
	)
	{
		BX.unbind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable);
	}

	BX.bind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable = function(event) {
		if (event.keyCode == 8)
		{
			BX.PreventDefault(event);
			return false;
		}
	});
	setTimeout(function(){
		BX.unbind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable);
		BX.SocNetLogDestination.backspaceDisable = null;
	}, 5000);
};

BX.BXGCE.selectCallback = function(item, type, search, bUndeleted, name)
{
	if(!BX.findChild(BX('sonet_group_create_popup_users_item_post_' + name), { attr : { 'data-id' : item.id }}, false, false))
	{
		BX('sonet_group_create_popup_users_item_post_' + name).appendChild(
			BX.create("span", { 
				attrs : { 
					'data-id' : item.id 
				}, 
				props : { 
					className : "feed-add-post-destination feed-add-post-destination-" + type
				}, 
				children: [
					BX.create("input", { 
						attrs : { 
							'type' : 'hidden', 
							'name' : 'USER_CODES[]',
							'value' : item.id 
						}
					}),
					BX.create("span", { 
						props : { 
							'className' : "feed-add-post-destination-text" 
						}, 
						html : item.name
					}),
					BX.create("span", { 
						props : { 
							'className' : "feed-add-post-del-but"
						}, 
						events : {
							'click' : function(e){
								BX.SocNetLogDestination.deleteItem(item.id, type, name);
								BX.PreventDefault(e);
							}, 
							'mouseover' : function(){
								BX.addClass(this.parentNode, 'feed-add-post-destination-hover');
							}, 
							'mouseout' : function(){
								BX.removeClass(this.parentNode, 'feed-add-post-destination-hover');
							}
						}
					})
				]
			})
		);
	}

	BX('sonet_group_create_popup_users_input_post_' + name).value = '';

	BX.SocNetLogDestination.BXfpSetLinkName({
		formName: name,
		tagInputName: 'sonet_group_create_popup_users_tag_post_' + name,
		tagLink1: BX.message('SONET_GCE_T_DEST_LINK_1'),
		tagLink2: BX.message('SONET_GCE_T_DEST_LINK_2')
	});
};

BX.BXGCE.openDialogCallback = function()
{
	BX.PopupWindow.setOptions({
		'popupZindex': 2100
	});
	BX.SocNetLogDestination.BXfpOpenDialogCallback.apply(this, arguments);
};

BX.BXGCE.bindActionLink = function(oBlock)
{
	if (
		oBlock === undefined
		|| oBlock == null
	)
	{
		return;
	}

	BX.bind(oBlock, "click", function(e)
	{
		BX.PopupMenu.destroy('invite-dialog-usertype-popup');

		var arItems = [
			{
				text : BX.message('SONET_GCE_T_DEST_EXTRANET_SELECTOR_INVITE'),
				id : 'sonet_group_create_popup_action_invite',
				className : 'menu-popup-no-icon',
				onclick: function() { BX.BXGCE.onActionSelect('invite'); }
			},
			{
				text : BX.message('SONET_GCE_T_DEST_EXTRANET_SELECTOR_ADD'),
				id : 'sonet_group_create_popup_action_add',
				className : 'menu-popup-no-icon',
				onclick: function() { BX.BXGCE.onActionSelect('add'); }
			}
		];

		var arParams = {
			offsetLeft: -14,
			offsetTop: 4,
			zIndex: 1200,
			lightShadow: false,
			angle: {position: 'top', offset : 50},
			events : {
				onPopupShow : function(ob)
				{

				}
			}
		};
		BX.PopupMenu.show('sonet_group_create_popup_action_popup', oBlock, arItems, arParams);
	});
};

BX.BXGCE.onActionSelect = function(action)
{
	if (action != 'add')
	{
		action = 'invite';
	}

	BX.BXGCE.lastAction = action;

	BX('sonet_group_create_popup_action_title_link').innerHTML = BX.message('SONET_GCE_T_DEST_EXTRANET_SELECTOR_' + (action == 'invite' ? 'INVITE' : 'ADD'));

	if (action == 'invite')
	{
		BX('sonet_group_create_popup_action_block_invite').style.display = 'block';
		BX('sonet_group_create_popup_action_block_invite_2').style.display = 'block';
		BX('sonet_group_create_popup_action_block_add').style.display = 'none';
	}
	else
	{
		BX('sonet_group_create_popup_action_block_invite').style.display = 'none';
		BX('sonet_group_create_popup_action_block_invite_2').style.display = 'none';
		BX('sonet_group_create_popup_action_block_add').style.display = 'block';
	}
	BX('sonet_group_create_popup_action_block_' + action).style.display = 'block';
	BX('sonet_group_create_popup_action_block_' + (action == 'invite' ? 'add' : 'invite')).style.display = 'none';

	BX.PopupMenu.destroy('sonet_group_create_popup_action_popup');
};

BX.BXGCE.showError = function(errorText)
{
	if (BX('sonet_group_create_error_block_content'))
	{
		BX('sonet_group_create_error_block_content').innerHTML = errorText;

		if (BX('sonet_group_create_error_block'))
		{
			BX('sonet_group_create_error_block').style.display = "block";
		}
	}
};

BX.BXGCE.showMessage = function()
{
};

BX.BXGCE.disableSubmitButton = function(bDisable)
{
	bDisable = !!bDisable;
	
	var oButton = BX("sonet_group_create_popup_form_button_submit");
	if (oButton)
	{
		if (bDisable)
		{
			BX.addClass(oButton, "popup-window-button-disabled");
			BX.addClass(oButton, "popup-window-button-wait");
			oButton.style.cursor = 'auto';
			BX.unbind(oButton, "click", BXGCESubmitForm);
		}
		else
		{
			BX.removeClass(oButton, "popup-window-button-disabled");
			BX.removeClass(oButton, "popup-window-button-wait");
			oButton.style.cursor = 'pointer';
			BX.bind(oButton, "click", BXGCESubmitForm);
		}
	}
}

})();
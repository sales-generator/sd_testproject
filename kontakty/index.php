<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("keywords", "Контакты");
$APPLICATION->SetPageProperty("description", "121059, г. Москва, Бережковская набережная, д.20, стр.64, 3-й этаж info@slovodelo.ru");
$APPLICATION->SetTitle("Контакты");

$arEmployee = array(
		array(
				'NAME' => "Муравьева Валентина",
				"POST" => "Коммерческий директор",
				"PHONE" => "доб. 101",
				"ICQ" => "323-081-904",
				"EMAIL" => "vm@slovodelo.ru",
		"IMAGES" => "/upload/images/Muravjova.png"
			),
		array(
				'NAME' => "Боброва Юлия",
				"POST" => "Старший менеджер",
				"PHONE" => "доб. 105",
				"ICQ" => "649-704-021",
				"EMAIL" => "julia@slovodelo.ru",
		"IMAGES" => "/upload/images/Bobrova.png"
			),
		array(
				'NAME' => "Грек Александра",
				"POST" => "Менеджер по работе с клиентами",
				"PHONE" => "доб. 102",
				"ICQ" => "194-403-437",
				"EMAIL" => "ag@slovodelo.ru",
		"IMAGES" => "/upload/images/Grek.png"
			),
		array(
				'NAME' => "Самсонова Екатерина",
				"POST" => "Менеджер по работе с клиентами",
				"EMAIL" => "ks@slovodelo.ru",
		"IMAGES" => "/upload/images/Samsonova.png"
			),
		array(
				'NAME' => "Кривцова Александра",
				"POST" => "Менеджер по работе с клиентами",
				"PHONE" => "доб. 103",
				"ICQ" => "391-277-585, gorreklama",
				"EMAIL" => "axandra@slovodelo.ru",
		"IMAGES" => "/upload/images/Krivtsova.png"
			),				
		array(
				'NAME' => "Капустина Яна",
				"POST" => "Менеджер по работе с клиентами",
				"EMAIL" => "yana@slovodelo.ru",
		        "IMAGES" => "/upload/images/Yana.jpg"
			),

        array(
				'NAME' => "Царяпкина Юлия",
				"POST" => "Менеджер по сувенирной продукции ",
				"EMAIL" => "yt@slovodelo.ru",
		        "IMAGES" => "/upload/images/Caryapkina.png"
			),

		array(
				'NAME' => "Николаева Елена",
				"POST" => "Менеджер по сувенирной продукции",
				"PHONE" => "доб. 100",
				"ICQ" => "390-358-461",
				"EMAIL" => "nik@slovodelo.ru",
		        "IMAGES" => "/upload/images/Nikolaeva.png"
			)
	);
?> 

<div class="row"> 
	<div class="col-4"> 
		<div class="employee nomargin"> <?foreach($arEmployee as $person):?> 
			<div class=""> 
				<div class="contact-item row"> 
					<div class="col-4"> 
						<div class="circle"> 
							<div class="holder"> <a> <img src="<?=$person["IMAGES"]?>"  /> </a> </div>
						 </div>
					 </div>
				 
					<div class="col-8" style="padding: 6px 0px 0px 6px;"> 
						<p><strong><?=$person["NAME"]?></strong> <?//=$person["PHONE"]?></p>
					 
						<p><?=$person["POST"]?></p>
					 
						<div> <a class="modalbox info-mail" href="#inline" onclick="$('#inline').show();jQuery('#mngr_email').val('<?=$person["EMAIL"]?>');jQuery('#mngr_email').focus();" > <?=$person["EMAIL"]?> </a> </div>
					 </div>
				 </div>
			 </div>
		 <?endforeach;?> </div>
	 </div>
 
	<div class="col-8 text-center" style="padding-top: 50px;"> 
		<div class="circle-container"> 
			<div class="circle-content"> 
				<div id="ya_map" style="width: 640px; height: 640px;"></div>
			 </div>
		 </div>
	 
		<div class="display-inline-block" style="margin-top: 15px;"> 
<!--  <tr>
						<td><strong>Адрес</strong>:</td>
						<td><span itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">Бережковская набережная д. 20 стр 64</span></td>
					</tr> -->
 
			<table class="text-left"> 
				<tbody> 
					<tr> <td colspan="2" class="hidden"><span itemprop="name">Словодело</span></td> </tr>
							 
					<tr> <td colspan="2" class="text-center"> <?/*<img id="bxid_285233" src="/upload/images/qr-code.gif"  />*/?> </td> </tr>
				 </tbody>
			 </table>
		 </div>
	 </div>
 </div>
	 
<table style="padding-left: 112px;"> 		 
	<tbody> 
		<tr> <td style="padding-right: 310px;"><strong>Телефон</strong>:</td> <td><span class="phones" itemprop="telephone">+7 495 207-75-77</span></td> </tr>
			 
		<tr> <td><strong>Адрес офиса/производства</strong>:</td> <td><span>121059, г. Москва, Бережковская набережная, д.20, стр.64, 3-й этаж</span></td> </tr>
		 
		<tr> <td><strong>E-mail</strong>:</td> <td><a onclick="_gaq.push(['_trackEvent', 'email', 'send']); yaCounter31086026.reachGoal('ym_email'); return true;" href="mailto:info@slovodelo.ru" title="Напишите нам" itemprop="email" >info@slovodelo.ru</a></td> </tr>
				 
		<tr> 		<td><strong>Время работы офиса</strong>:</td> 		<td><span>с 10:00 до 18:00 понедельник-пятница.</span></td> 	 </tr>
				 
		<tr> 		<td><strong>Время работы производства</strong>:</td> 		<td><span>круглосуточно.</span></td> 	 </tr>
				 
		<tr> 		<td><strong>Время работы контактного телефона</strong>:</td> 		<td><span>с 10:00 до 18:00 понедельник-пятница.</span></td> 	 </tr>
		</tbody>
 </table>
 
<h2>Схема проезда</h2>
	 
<p align="center"><img src="/images/site.png" width="600" height="400"  /></p>
	 
<p>Для автомобилей — заезд с Бережковской набережной.</p>
 
<p>Городским транспортом — от м. Киевская: автобус 205, 119, 91, 791, троллейбус 17, 34 до остановки ТЭЦ № 12.</p>
 
<br />
 
<!-- Форма отправки сообщения -->
 
<div id="inline" class="contact-form-wrapper"> 
	<h2>Отправить сообщение</h2>
 <form id="contact" name="contact" action="#" method="post"> <input type="hidden" id="mngr_email" name="mngr_email" value="info@slovodelo.ru" /> 
		<table> 
			<tbody> 
				<tr><td> </td></tr>
			 
				<tr><td><label for="email">Ваше имя</label></td> <td><input type="" id="name" name="name" class="txt2" /></td></tr>
			 
				<tr><td><label for="email">Ваш E-mail</label></td> <td><input type="email" id="email" name="email" class="txt2" /></td></tr>
			 
				<tr><td> <label for="msg">Cообщение</label></td> <td> <textarea id="msg" name="msg" class="txtarea"></textarea></td></tr>
			 </tbody>
		 </table>
	 <button id="send" class="text-uppercase">Отправить</button> </form> </div>
 <?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
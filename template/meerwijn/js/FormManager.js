String.prototype.trim = function() { return this.replace(/(^\s*)|(\s*$)/g, ""); } 

var FormManager = {
	formName : "ShopForm",
	multiple : 6,
	
	getForm : function(){
		return document.forms[this.formName];
	},
	
	isCompatible : function(){
		if(this.compatible == undefined){
			this.compatible = false;
			if(
			   document &&
			   document.body &&
			   document.body.innerHTML &&
			   document.getElementById &&
			   document.getElementsByTagName &&
			   document.body.style
			){
				this.compatible = true;
			}
		}
		return this.compatible;
	},
	
	changeQuantity : function(itemId, change){
		if(this.isCompatible() == true){
			var form, newQuantityText, newQuantity, price, units, newTotal, newTotalText;
			if(!change) change = 0;
			form = this.getForm();
			
			// Calculate and display new quantity based on requested change.
			newQuantityText = form['ttp_basket[' + itemId + '][quantity]'].value.trim();
			if(newQuantityText.length == 0) newQuantity = 0 + change;
			else newQuantity = parseInt(newQuantityText) + change;
			if(newQuantity < 0) newQuantity = 0;
			form['ttp_basket[' + itemId + '][quantity]'].value = newQuantity;
			
			// Calculate and display new row total.
			if(form['prijs_korting_' + itemId].value != "") price = parseFloat(form['prijs_korting_' + itemId].value);
			else price = parseFloat(form['prijs_' + itemId].value);
			units = parseInt(form['eenheid_' + itemId].value);
			newTotal = newQuantity * price * units;
			newTotalText = this.formatMoneyText(newTotal);
			document.getElementById("td_rowtotal_" + itemId).innerHTML = "<nobr>&#8364;&nbsp;" + newTotalText + "</nobr>";
		}
	},
	
	changeQuantityCredits : function(itemId, change){
		if(this.isCompatible() == true){
			var form, newQuantityText, newQuantity, price, units, newTotal, newTotalText;
			if(!change) change = 0;
			form = this.getForm();
			
			// Calculate and display new quantity based on requested change.
			newQuantityText = form['ttp_basket[' + itemId + '][quantity]'].value.trim();
			if(newQuantityText.length == 0) newQuantity = 0 + change;
			else newQuantity = parseInt(newQuantityText) + change;
			if(newQuantity < 0) newQuantity = 0;
			form['ttp_basket[' + itemId + '][quantity]'].value = newQuantity;
			
			// Calculate and display new row total.
			if(form['prijs_korting_' + itemId].value != "") price = parseFloat(form['prijs_korting_' + itemId].value);
			else price = parseFloat(form['prijs_' + itemId].value);
			units = parseInt(form['eenheid_' + itemId].value);
			newTotalText = newQuantity * price * units;
			document.getElementById("td_rowtotal_" + itemId).innerHTML = "<nobr>" + newTotalText + "</nobr>";
		}
	},
	checkMultiples : function(){
		var returnValue = true;
		if(this.isCompatible() == true){
			var inputs = this.getForm().getElementsByTagName("input");
			var bottles = 0;
			var units;
			var quantity;
			var quantityText;
			var iLimit = inputs.length;
			for(var i = 0; i < iLimit; i++){
				if(inputs[i].name.substring(0, 7) == "eenheid"){
					units = parseInt(inputs[i].value);
				}
				else if(inputs[i].name.indexOf("quantity") != -1){
					quantityText = inputs[i].value.trim();
					if(quantityText.length == 0) quantity = 0;
					else quantity = parseInt(quantityText);
					bottles += units * quantity;
				}
			}
			
			if(bottles % 6 != 0){
				var message = document.getElementById("foutmelding");
				message.style.display = "block";
				location.hash = "uitleg";
				returnValue = false;
			}
		}			
		return returnValue;
	},
	checkCredits : function(){
		var returnValue = true;
		if(this.isCompatible() == true){
			var form;
			var inputs = this.getForm().getElementsByTagName("input");
			var totcredits = 0;
			var units;
			var quantity;
			var quantityText;
			var price;
			var priceText;
			var price2;
			var priceText2;
			var iLimit = inputs.length;

			var CreditsAmount;

			for(var i = 0; i < iLimit; i++){
				if(inputs[i].name.indexOf("quantity") != -1) {
					quantityText = inputs[i].value.trim();
					if(quantityText.length == 0) quantity = 0;
					else quantity = parseInt(quantityText);
				} else if(inputs[i].name.indexOf("prijs_korting_") != -1) {
					priceText = inputs[i].value.trim();
					if(priceText.length == 0) price = 0;
					else price = parseInt(priceText);
				} else if(inputs[i].name.indexOf("prijs_") != -1) {
					priceText2 = inputs[i].value.trim();
					if(priceText2.length == 0) price2 = 0;
					else price2 = parseInt(priceText2);
					if (price == "0") {
						totcredits += price2 * quantity;
					} else {
						totcredits += price * quantity;
					}
				}
			}


			form = this.getForm();
			CreditsAmount = form['amountcredits'].value.trim();

			if ( (CreditsAmount - totcredits) < 0 ) {
				var message = document.getElementById("foutmelding");
				message.style.display = "block";
				location.hash = "uitleg";
				returnValue = false;
			}


		}
		return returnValue;
	},
	
	formatMoneyText : function(num){
		var numText;
		
		if(num == 0){
			numText = "0.00";
		}
		else{
			numText = String(Math.round(num * 100));
			numText = numText.substring(0, numText.length - 2) + "." + numText.substring(numText.length - 2);
		}
		return numText;
	}
};
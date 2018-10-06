function filter() {
	var inputWho, inputWhen, inputComment, filterWho, filterWhen, filterComment, table, tr, results, origsum, newresults, newsum, tdWho, tdWhen, tdAmount, tdComment, i;
	inputWho = document.getElementById("who");
	inputWhen = document.getElementById("when");
	inputComment = document.getElementById("comment");
	filterWho = inputWho.value.toUpperCase();
	filterWhen = inputWhen.value.toUpperCase();
	filterComment = inputComment.value.toUpperCase();
	table = document.getElementById("list");
	tr = table.getElementsByTagName("tr");
	results = document.getElementById("results");
	origsum = document.getElementById("sum");
	newresults = 0;
	newsum = 0.0;

	for (i = 0; i < tr.length; i++) {
		tdWho = tr[i].getElementsByTagName("td")[0];
		tdWhen = tr[i].getElementsByTagName("td")[1];
		tdAmount = tr[i].getElementsByTagName("td")[2];
		tdComment = tr[i].getElementsByTagName("td")[3];

		if (tdWho && tdWhen && tdAmount && tdComment) {
			if ((tdWho.innerHTML.toUpperCase().indexOf(filterWho) > -1) && (tdWhen.innerHTML.toUpperCase().indexOf(filterWhen) > -1) && (tdComment.innerHTML.toUpperCase().indexOf(filterComment) > -1)) {
				tr[i].style.display = "";
				newresults++;
				newsum += parseFloat(tdAmount.innerHTML);
			} else {
				tr[i].style.display = "none";
			}
		}
	}

	results.innerHTML = newresults;
	origsum.innerHTML = parseFloat(newsum).toFixed(2);
}

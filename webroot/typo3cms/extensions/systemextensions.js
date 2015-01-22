// manually maintained list of system extensions
// mb, starting 2015-01-22

// merge list 'systemExtensionList' into list 'extensionList'

var systemExtensionList = [
	{"key":"core","latest":"latest","versions":["latest"]},
	{"key":"css_styled_content","latest":"latest","versions":["4.7","6.2","latest","latest"]},
	{"key":"dbal","latest":"latest","versions":["1.0.0","6.2","latest","latest"]},
	{"key":"documentation","latest":"latest","versions":["6.2","latest","latest"]},
	{"key":"felogin","latest":"latest","versions":["4.7","6.2","latest","latest"]},
	{"key":"form","latest":"latest","versions":["4.7","6.2","latest","latest"]},
	{"key":"indexed_search","latest":"latest","versions":["6.2","latest","latest"]},
	{"key":"linkvalidator","latest":"latest","versions":["6.2","latest","latest"]},
	{"key":"openid","latest":"latest","versions":["4.7","6.2","latest","latest"]},
	{"key":"recycler","latest":"latest","versions":["4.7","6.2","latest","latest"]},
	{"key":"rsaauth","latest":"latest","versions":["4.7","6.2","latest","latest"]},
	{"key":"rtehtmlarea","latest":"latest","versions":["4.7","6.2","latest","latest"]},
	{"key":"saltedpasswords","latest":"latest","versions":["4.7","6.2","latest","latest"]},
	{"key":"scheduler","latest":"latest","versions":["4.7","6.2","latest","latest"]},
	{"key":"sys_action","latest":"latest","versions":["4.7","6.2","latest","latest"]},
	{"key":"taskcenter","latest":"latest","versions":["4.7","6.2","latest","latest"]},
	{"key":"workspaces","latest":"latest","versions":["6.2","latest","latest"]}
];

// var extensionList needs to exist at this point
// merge var systemExtensionList into var extensionList
// we expect the lists to be sorted

var t3extkey;
var j = 0;

// Loop on all extension to find an exact match first
for (var i = 0; i < systemExtensionList.length; i++) {
	t3extkey = systemExtensionList[i].key;
	while (j < extensionList.length) {
		if (t3extkey < extensionList[j].key) {
			extensionList.splice(j, 0, systemExtensionList[i]);
			t3extkey = '';
			j++;
			break;
		} else if (extensionList[j].key == t3extkey) {
			extensionList.splice(j, 1, systemExtensionList[i]);
			t3extkey = '';
			j++;
			break;
		} else {
			j++;
		}
	}
	if (t3extkey.length) {
		extensionList.push(systemExtensionList[i]);
		j = extensionList.length;
	}
}

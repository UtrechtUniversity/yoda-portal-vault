import re

lines = open('intake_metadata.php', 'r').read().splitlines()

replaceVals = {
	"<" : "&lt;",
	">" : "&gt;"
}

def getIndents(n):
	ident = ""
	for i in range(n):
		ident += "\t"
	return ident

lastKey = []
indent = 1

xml = ["<schema>"]

for l in lines:

	if re.match('[ \t]+\/\/', l):
		continue
	if "array" in l:
		key = re.findall(ur'"(.*)"[ \t]*=>[ \t]*array[ \t]*\(', l)
		if len(key) > 0:
			key = key[0]
		elif not l.startswith("public"):
			key = "option"
		else: 
			continue
		lastKey.append(key)
		xml.append("%s<%s>" % (getIndents(indent), key))
		indent += 1
	else:
		elems = re.findall(ur'"(.*)"[ \t]*=>[ \t]*("?)([^\n"]*|true|false)\2', l)
		if(len(elems) > 0):
			value = elems[0][2]
			if value in replaceVals.keys():
				for char in replaceVals.keys():
					value = value.replace(char, replaceVals[char])
			xml.append("%s<%s>%s</%s>" %(getIndents(indent), elems[0][0], value, elems[0][0]))
		elif ")" in l and len(lastKey) > 0:
			indent -= 1
			xml.append("%s</%s>" %(getIndents(indent), lastKey.pop()))
		else:
			matches = re.findall('[ \t]+"?([a-zA-Z 0-9]+)"?,?', l)
			if len(matches) == 1:
				xml.append("%s<option>%s</option>" %(getIndents(indent), matches[0]))

xml.append("</schema>")

xmlmd = open('intake_metadata.xml', 'wb')
for x in xml:
	xmlmd.write(x + "\n")
xmlmd.close()


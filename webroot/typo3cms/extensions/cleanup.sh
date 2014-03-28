#!/bin/bash

for E in $(find . -maxdepth 1 -type d); do
	pushd $E >/dev/null

	echo "Extension: $E"
	echo -n "Directories: "
	ls
	HIGHEST_VERSION=$(find . -maxdepth 1 -type d | grep "^\./[0-9]" | sort -n | tail -n 1)
	echo "Highest: ${HIGHEST_VERSION}"
	for V in $(find . -maxdepth 1 -type d | grep "^\./[0-9]"); do
		if [ "$V" != "${HIGHEST_VERSION}" ]; then
			if [ -f "$V/manual.sxw" ]; then
				mkdir -p TO_DELETE
				mv $V TO_DELETE/
			fi
		fi
	done
	echo

	popd >/dev/null
done


    function renderLocalResults(url) {
        const params = url.searchParams;
        const form = catalog.querySelector('#directory-filters');
        const search = params.get('q')?.trim() ?? '';
        const needle = search.toLocaleLowerCase();
        const filters = ['category', 'pricing', 'link_type'];
        const sort = params.get('sort') || 'dr';
        if (!Array.from(form.elements.sort.options).some(option => option.value === sort)) return false;
        const rows = snapshot.filter(row =>
            (!needle || [row.name, row.url].some(value => value.toLocaleLowerCase().includes(needle))) &&
            filters.every(key => !params.has(key) ||
                row[key] === (params.get(key) === 'unknown' ? null : params.get(key)))
        );
        if (sort === 'name_desc') {
            const names = new Intl.Collator(undefined, { sensitivity: 'base' });
            rows.sort((a, b) => names.compare(b.name, a.name) || a.id - b.id);
        }
        if (sort === 'dr' || sort === 'dr_asc') {
            rows.sort((a, b) => a.dr == null ? (b.dr == null ? 0 : 1) :
                b.dr == null ? -1 : sort === 'dr' ? b.dr - a.dr : a.dr - b.dr);
        }

        const pageSize = 50;
        const last = Math.max(1, Math.ceil(rows.length / pageSize));
        const page = Number(params.get('page') || 1);
        if (!Number.isInteger(page) || page < 1 || page > last) return false;

        for (const control of form.querySelectorAll('[name]')) {
            const value = params.get(control.name) ?? (control.name === 'sort' ? 'dr' : '');
            if (control.type === 'radio') control.checked = control.value === value;
            else if (control.name !== 'q' || control.value.trim() !== value.trim()) control.value = value;
        }

        const start = (page - 1) * pageSize;
        const visible = rows.slice(start, start + pageSize);
        const body = catalog.querySelector('.directory-table tbody');
        body.innerHTML = visible.map(row => row.html).join('');
        if (needle) {
            visible.forEach((row, index) => {
                const cell = body.rows[index].cells[0];
                const name = cell.querySelector('.directory-name');
                const text = Array.from(name.childNodes).find(node => node.nodeType === Node.TEXT_NODE && node.textContent.trim());
                if (text) text.replaceWith(markDirectoryMatch(row.name, search));
                if (row.domain.toLocaleLowerCase().includes(needle)) {
                    const domain = document.createElement('div');
                    domain.className = 'directory-match-domain';
                    domain.append(markDirectoryMatch(row.domain, search));
                    cell.append(domain);
                }
            });
        }
        catalog.querySelector('.directory-grid').hidden = !rows.length;
        catalog.querySelector('[data-directory-empty]').hidden = !!rows.length;
        const total = rows.length.toLocaleString();
        const noun = needle ? (rows.length === 1 ? 'match' : 'matches') : (rows.length === 1 ? 'directory' : 'directories');
        const count = (rows.length ? `${(start + 1).toLocaleString()}–${(start + visible.length).toLocaleString()} of ` : '') + total + ' ' + noun;
        catalog.querySelector('[data-directory-count]').textContent = count;
        catalog.querySelector('[data-directory-announcement]').textContent = count;

        const linkUrl = changes => {
            const target = new URL(url);
            for (const [key, value] of Object.entries(changes)) {
                if (value == null) target.searchParams.delete(key);
                else target.searchParams.set(key, value);
            }
            return target.href;
        };
        const chips = catalog.querySelector('.directory-filter-chips');
        chips.replaceChildren();
        for (const key of filters) {
            if (!params.has(key)) continue;
            const control = form.elements.namedItem(key);
            const label = key === 'category' ? control.selectedOptions[0]?.textContent :
                Array.from(control).find(input => input.checked)?.nextElementSibling.textContent;
            if (!label) continue;
            const chip = document.createElement('a');
            chip.href = linkUrl({ [key]: null, page: null });
            chip.className = 'directory-control directory-chip directory-chip-active';
            chip.dataset.directoryNavigation = '';
            chip.setAttribute('aria-label', 'Remove ' + label.trim() + ' filter');
            const cross = document.createElement('span');
            cross.className = 'directory-chip-remove';
            cross.setAttribute('aria-hidden', 'true');
            cross.textContent = '×';
            chip.append(label.trim(), cross);
            chips.append(chip);
        }
        catalog.querySelector('[data-directory-clear-all]').hidden =
            !search && sort === 'dr' && !filters.some(key => params.has(key));

        const headings = catalog.querySelectorAll('.directory-table th');
        for (const [index, ascending, descending] of [[0, 'name', 'name_desc'], [1, 'dr_asc', 'dr']]) {
            const heading = headings[index];
            const direction = sort === ascending ? 'ascending' : sort === descending ? 'descending' : null;
            if (direction) heading.setAttribute('aria-sort', direction);
            else heading.removeAttribute('aria-sort');
            const link = heading.querySelector('a');
            const defaultSort = index === 0 ? ascending : descending;
            const reverseSort = index === 0 ? descending : ascending;
            link.href = linkUrl({ sort: sort === defaultSort ? reverseSort : defaultSort, page: null });
            link.querySelector('span').textContent = direction === 'ascending' ? '↑' : direction === 'descending' ? '↓' : '↕';
        }

        const pagination = catalog.querySelector('[data-directory-pagination]');
        pagination.replaceChildren();
        if (last > 1) {
            const nav = document.createElement('nav');
            nav.className = 'flex flex-wrap items-center gap-2 text-sm';
            nav.setAttribute('aria-label', 'Directory pages');
            const addPage = (number, label) => {
                const item = document.createElement(number === page ? 'span' : 'a');
                item.className = 'directory-control';
                item.textContent = label;
                if (number === page) {
                    item.classList.add('directory-current-page');
                    item.setAttribute('aria-current', 'page');
                } else item.href = linkUrl({ page: number === 1 ? null : number });
                nav.append(item);
            };
            if (page > 1) addPage(page - 1, 'Previous');
            for (let number = 1; number <= last; number++) addPage(number, number);
            if (page < last) addPage(page + 1, 'Next');
            pagination.append(nav);
        }
        return true;
    }

    function markDirectoryMatch(value, search) {
        const fragment = document.createDocumentFragment();
        const escaped = Array.from(search, character => '\\^$.*+?()[]{}|'.includes(character) ? '\\' + character : character).join('');
        value.split(new RegExp('(' + escaped + ')', 'iu')).forEach((part, index) => {
            if (index % 2) {
                const mark = document.createElement('mark');
                mark.textContent = part;
                fragment.append(mark);
            } else fragment.append(part);
        });
        return fragment;
    }

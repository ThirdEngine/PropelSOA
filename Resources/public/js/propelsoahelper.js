/**
 * This function will pluralize a word based on the standard english pluralizer built into Propel. This
 * is a parallel implementation, it will not call the server.
 */
function pluralize(root)
{
    var result,
        regex,
        pattern,
        uncountable = [
        'sheep',
        'fish',
        'deer',
        'series',
        'species',
        'money',
        'rice',
        'information',
        'equipment',
        'news',
        'people'
    ];

    if (uncountable.indexOf(root.toLowerCase()) != -1)
    {
        return root;
    }


    var irregular = {
        'leaf': 'leaves',
        'loaf': 'loaves',
        'move': 'moves',
        'foot': 'feet',
        'goose': 'geese',
        'genus': 'genera',
        'sex': 'sexes',
        'ox': 'oxen',
        'child': 'children',
        'man': 'men',
        'tooth': 'teeth',
        'person': 'people',
        'wife': 'wives',
        'mythos': 'mythoi',
        'testis': 'testes',
        'numen': 'numina',
        'quiz': 'quizzes',
        'alias': 'aliases'
    };

    for (pattern in irregular)
    {
        regex = new RegExp(pattern + '$', 'i');
        result = irregular[pattern];

        if (regex.test(root))
        {
            var working = root.replace(regex, result);

            var uppercaseRegex = new RegExp('/^[A-Z]*/');
            if (uppercaseRegex.test(root))
            {
                working = working.charAt(0).toUpperCase() + working.slice(1);
            }

            return working;
        }
    }
    
    
    var plural = {
        '(matr|vert|ind)(ix|ex)': '\\1ices',
        '(alumn|bacill|cact|foc|fung|nucle|radi|stimul|syllab|termin|vir)us': '\\1i',
        '(buffal|tomat)o': '\\1oes',
    
        'x' : 'xes',
        'ch': 'ches',
        'sh': 'shes',
        'ss': 'sses',
    
        'ay': 'ays',
        'ey': 'eys',
        'iy': 'iys',
        'oy': 'oys',
        'uy': 'uys',
        'y' : 'ies',
    
        'ao': 'aos',
        'eo': 'eos',
        'io': 'ios',
        'oo': 'oos',
        'uo': 'uos',
        'o' : 'os',
    
        'us': 'uses',
    
        'cis': 'ces',
        'sis': 'ses',
        'xis': 'xes',
    
        'zoon': 'zoa',
    
        'itis': 'itis',
        'ois' : 'ois',
        'pox' : 'pox',
        'ox'  : 'oxes',
    
        'foot' : 'feet',
        'goose': 'geese',
        'tooth': 'teeth',
        'quiz': 'quizzes',
        'alias': 'aliases',
    
        'alf' : 'alves',
        'elf' : 'elves',
        'olf' : 'olves',
        'arf' : 'arves',
        'nife': 'nives',
        'life': 'lives'
    };

    for (pattern in plural)
    {
        result = plural[pattern];
        regex = new RegExp(pattern + '$', 'i');

        if (regex.test(root))
        {
            return root.replace(regex, result);
        }
    }

    return root + 's';
}

function objectHasKey(haystack, needle)
{
    for (var key in haystack) {
        if (key == needle && haystack.hasOwnProperty(key)) {
            return true;
        }
    }

    return false;
}


function logKeys(object)
{
    for (var key in object) {
        console.log(key);
        console.log(object[key]);
    }
}


JSON.stringify = JSON.stringify || function (obj) {
    var t = typeof (obj);
    if (t != "object" || obj === null) {
        // simple data type
        if (t == "string") obj = '"'+obj+'"';
        return String(obj);
    }
    else {
        // recurse array or object
        var n, v, json = [], arr = (obj && obj.constructor == Array);
        for (n in obj) {
            v = obj[n]; t = typeof(v);
            if (t == "string") v = '"'+v+'"';
            else if (t == "object" && v !== null) v = JSON.stringify(v);
            json.push((arr ? "" : '"' + n + '":') + String(v));
        }
        return (arr ? "[" : "{") + String(json) + (arr ? "]" : "}");
    }
};

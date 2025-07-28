// DJ LEVELからの差分計算ユーティリティ

// DJ LEVELを計算
function calculateDJLevel(score, totalNotes) {
    if (!totalNotes || totalNotes <= 0) return null;
    
    const maxScore = totalNotes * 2;
    const ratio = score / maxScore;
    
    if (ratio >= 8/9) return 'AAA';
    if (ratio >= 7/9) return 'AA';
    if (ratio >= 6/9) return 'A';
    if (ratio >= 5/9) return 'B';
    if (ratio >= 4/9) return 'C';
    if (ratio >= 3/9) return 'D';
    if (ratio >= 2/9) return 'E';
    return 'F';
}

// スコアからDJ LEVELとの差分を計算
function calculateScoreDifference(score, totalNotes) {
    if (!totalNotes || totalNotes <= 0 || !score) {
        return { difference: null, prefix: '', level: null };
    }
    
    const maxScore = totalNotes * 2;
    
    // MAXスコア（理論値）との比較
    const maxDiff = maxScore - score;
    if (maxDiff === 0) {
        return { difference: 0, prefix: '', level: 'MAX' };
    }
    
    // 各ランクのボーダースコアを計算
    const borders = {
        'AAA': Math.floor(maxScore * 8/9),
        'AA': Math.floor(maxScore * 7/9),
        'A': Math.floor(maxScore * 6/9),
        'B': Math.floor(maxScore * 5/9),
        'C': Math.floor(maxScore * 4/9),
        'D': Math.floor(maxScore * 3/9),
        'E': Math.floor(maxScore * 2/9)
    };
    
    // 全ての可能な差分を計算して最小のものを選択
    let candidates = [];
    
    // MAXとの差分
    candidates.push({
        difference: Math.abs(maxDiff),
        prefix: maxDiff > 0 ? '-' : '+',
        level: 'MAX'
    });
    
    // 各ランクとの差分
    for (const [level, border] of Object.entries(borders)) {
        // 筐体と同じ計算方式: score - (border + 1)
        const diff = score - (border + 1);
        candidates.push({
            difference: Math.abs(diff),
            prefix: diff >= 0 ? '+' : '-',
            level: level
        });
    }
    
    // 最も差分の小さいものを選択
    candidates.sort((a, b) => a.difference - b.difference);
    
    return candidates[0];
}

// 差分表記をフォーマット
function formatScoreDifference(score, totalNotes) {
    const result = calculateScoreDifference(score, totalNotes);
    
    if (!result || (result.difference === null && result.difference !== 0)) {
        return '';
    }
    
    // 理論値（MAX）と完全に一致する場合は「MAX」のみ表示
    const maxScore = totalNotes * 2;
    if (score === maxScore) {
        return 'MAX';
    }
    
    
    return `${result.level}${result.prefix}${result.difference}`;
}
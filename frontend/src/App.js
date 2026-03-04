import React, { useState, useEffect, useCallback, useRef } from 'react';
import './App.css';
import SearchBar from './components/SearchBar';
import GameGrid from './components/GameGrid';

const API_BASE = process.env.REACT_APP_API_URL || 'http://localhost:8080/api';
const INITIAL_ROWS = 5;
const COLS = 4;
const INITIAL_LIMIT = INITIAL_ROWS * COLS;

function App() {
  const [displayedGames, setDisplayed] = useState([]);
  const [query, setQuery]             = useState('');
  const [loading, setLoading]         = useState(false);
  const [hasMore, setHasMore]         = useState(false);
  const [page, setPage]               = useState(1);
  const [totalCount, setTotalCount]   = useState(0);
  const debounceRef                   = useRef(null);

  const loadGames = useCallback(async (nextPage = 1, append = false) => {
    setLoading(true);
    try {
      const res  = await fetch(`${API_BASE}/games?page=${nextPage}&limit=${INITIAL_LIMIT}`);
      const data = await res.json();
      const games = data.games || [];
      setDisplayed(prev => append ? [...prev, ...games] : games);
      setTotalCount(data.total || 0);
      setHasMore((nextPage * INITIAL_LIMIT) < (data.total || 0));
      setPage(nextPage);
    } catch (err) {
      console.error('Failed to load games', err);
    } finally {
      setLoading(false);
    }
  }, []);

  const searchGames = useCallback(async (q) => {
    if (!q.trim()) {
      loadGames(1, false);
      return;
    }
    setLoading(true);
    try {
      const res  = await fetch(`${API_BASE}/search?q=${encodeURIComponent(q)}`);
      const data = await res.json();
      setDisplayed(data.games || []);
      setHasMore(false);
    } catch (err) {
      console.error('Search failed', err);
    } finally {
      setLoading(false);
    }
  }, [loadGames]);

  useEffect(() => {
    loadGames(1, false);
  }, [loadGames]);

  const handleSearch = (value) => {
    setQuery(value);
    clearTimeout(debounceRef.current);
    debounceRef.current = setTimeout(() => searchGames(value), 350);
  };

  const handleLoadMore = () => {
    if (!loading && hasMore) {
      loadGames(page + 1, true);
    }
  };

  return (
    <div className="App">
      <header className="App-header">
        <h1 className="App-title">Game Store</h1>
        <SearchBar value={query} onChange={handleSearch} />
      </header>
      <main className="App-main">
        {loading && displayedGames.length === 0 ? (
          <div className="loading">Loading games\u2026</div>
        ) : (
          <>
            <p className="results-count">
              {query.trim()
                ? `${displayedGames.length} result(s) for "${query}"`
                : `Showing ${displayedGames.length} of ${totalCount} games`}
            </p>
            <GameGrid games={displayedGames} />
            {hasMore && !query.trim() && (
              <button
                className="load-more-btn"
                onClick={handleLoadMore}
                disabled={loading}
              >
                {loading ? 'Loading\u2026' : 'Load More'}
              </button>
            )}
          </>
        )}
      </main>
    </div>
  );
}

export default App;

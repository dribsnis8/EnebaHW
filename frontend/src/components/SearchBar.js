import React from 'react';
import './SearchBar.css';

function SearchBar({ value, onChange }) {
  return (
    <div className="search-bar-wrapper">
      <input
        className="search-bar-input"
        type="text"
        placeholder="Search for a game…"
        value={value}
        onChange={e => onChange(e.target.value)}
        aria-label="Search games"
      />
      {value && (
        <button
          className="search-bar-clear"
          onClick={() => onChange('')}
          aria-label="Clear search"
        >
          ✕
        </button>
      )}
    </div>
  );
}

export default SearchBar;

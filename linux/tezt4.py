import os
import subprocess

# Define metadata for channels
channel_metadata = {
    "hd-bnt-1-hd": {"name": "БНТ 1", "tvg_id": "BNT1", "group_title": "Национални", "logo": "http://logos.epg.cloudns.org/bnt1.png"},
    "bnt-2": {"name": "БНТ 2", "tvg_id": "BNT2", "group_title": "Национални", "logo": "http://logos.epg.cloudns.org/bnt2.png"},
    "hd-bnt-3-hd": {"name": "БНТ 3", "tvg_id": "BNT3", "group_title": "Национални", "logo": "http://logos.epg.cloudns.org/bnt3.png"},
    "bnt-4": {"name": "БНТ 4", "tvg_id": "BNT4", "group_title": "Национални", "logo": "http://logos.epg.cloudns.org/bnt4.png"},
    "hd-btv-hd": {"name": "bTV", "tvg_id": "bTV", "group_title": "Национални", "logo": "http://logos.epg.cloudns.org/btv.png"},
    "hd-nova-tv-hd": {"name": "Nova TV", "tvg_id": "Nova", "group_title": "Национални", "logo": "http://logos.epg.cloudns.org/nova.png"},

    "btv-cinema": {"name": "bTV Cinema", "tvg_id": "bTVCinema", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/btvcinema.png"},
    "hd-btv-action-hd": {"name": "bTV Action", "tvg_id": "bTVAction", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/btvaction.png"},
    "btv-comedy": {"name": "bTV Comedy", "tvg_id": "bTVComedy", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/btvcomedy.png"},
    "btv-story": {"name": "bTV Story", "tvg_id": "bTVStory", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/btvstory.png"},
    "diema": {"name": "Diema", "tvg_id": "Diema", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/diema.png"},
    "diema-family": {"name": "Diema Family", "tvg_id": "DiemaFamily", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/diemafamily.png"},
    "kino-nova": {"name": "Kino Nova", "tvg_id": "KinoNova", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/kinonova.png"},
    "hd-star-channel-hd": {"name": "Star Channel", "tvg_id": "STARChannel", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/starchannel.png"},
    "hd-star-crime-hd": {"name": "Star Crime", "tvg_id": "STARCrime", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/starcrime.png"},
    "hd-star-life-hd": {"name": "Star Life", "tvg_id": "STARLife", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/starlife.png"},
    "hd-epic-drama-hd": {"name": "Epic Drama", "tvg_id": "EpicDrama", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/epicdrama.png"},
    #"amc": {"name": "AMC", "tvg_id": "amc", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/amc.png"},
    "axn": {"name": "AXN", "tvg_id": "AXN", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/axn.png"},
    "axn-black": {"name": "AXN Black", "tvg_id": "AXNBlack", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/axnblack.png"},
    "axn-white": {"name": "AXN White", "tvg_id": "AXNWhite", "group_title": "Филми", "logo": "http://logos.epg.cloudns.org/axnwhite.png"},

    "hd-diema-sport-hd": {"name": "Diema Sport", "tvg_id": "DiemaSport", "group_title": "Спорт", "logo": "http://logos.epg.cloudns.org/diemasport.png"},
    "hd-diema-sport-2-hd": {"name": "Diema Sport 2", "tvg_id": "DiemaSport2", "group_title": "Спорт", "logo": "http://logos.epg.cloudns.org/diemasport2.png"},
    "hd-diema-sport-3-hd": {"name": "Diema Sport 3", "tvg_id": "DiemaSport3", "group_title": "Спорт", "logo": "http://logos.epg.cloudns.org/diemasport3.png"},
    "hd-nova-sport-hd": {"name": "Nova Sport", "tvg_id": "NovaSport", "group_title": "Спорт", "logo": "http://logos.epg.cloudns.org/novasport.png"},
    "hd-eurosport-1-hd": {"name": "Eurosport 1", "tvg_id": "Eurosport1", "group_title": "Спорт", "logo": "http://logos.epg.cloudns.org/eurosport1.png"},
    "hd-eurosport-2-hd": {"name": "Eurosport 2", "tvg_id": "Eurosport2", "group_title": "Спорт", "logo": "http://logos.epg.cloudns.org/eurosport2.png"},
    "hd-max-sport-1-hd": {"name": "MAX Sport 1", "tvg_id": "MAXSport1", "group_title": "Спорт", "logo": "http://logos.epg.cloudns.org/maxsport1.png"},
    "hd-max-sport-2-hd": {"name": "MAX Sport 2", "tvg_id": "MAXSport2", "group_title": "Спорт", "logo": "http://logos.epg.cloudns.org/maxsport2.png"},
    "hd-max-sport-3-hd": {"name": "MAX Sport 3", "tvg_id": "MAXSport3", "group_title": "Спорт", "logo": "http://logos.epg.cloudns.org/maxsport3.png"},
    "hd-max-sport-4-hd": {"name": "MAX Sport 4", "tvg_id": "MAXSport4", "group_title": "Спорт", "logo": "http://logos.epg.cloudns.org/maxsport4.png"},
    "hd-ring-bg-hd": {"name": "RING BG", "tvg_id": "RING", "group_title": "Спорт", "logo": "http://logos.epg.cloudns.org/ring.png"},

    #"historytv": {"name": "History Channel", "tvg_id": "History", "group_title": "Научнопопулярни", "logo": "http://logos.epg.cloudns.org/history.png"},
    "hd-discovery-channel-hd": {"name": "Discovery Channel", "tvg_id": "Discovery", "group_title": "Научнопопулярни", "logo": "http://logos.epg.cloudns.org/discovery.png"},
    "hd-id-xtra-hd": {"name": "Investigation Discovery", "tvg_id": "ID", "group_title": "Научнопопулярни", "logo": "http://logos.epg.cloudns.org/id.png"},
    "hd-nat-geo-hd": {"name": "National Geographic", "tvg_id": "NatGeo", "group_title": "Научнопопулярни", "logo": "http://logos.epg.cloudns.org/natgeo.png"},
    "hd-nat-geo-wild-hd": {"name": "Nat Geo Wild", "tvg_id": "NatGeo Wild", "group_title": "Научнопопулярни", "logo": "http://logos.epg.cloudns.org/natgeowild.png"},
    #"animalplanet": {"name": "Animal Planet", "tvg_id": "AnimalPlanet", "group_title": "Научнопопулярни", "logo": "http://logos.epg.cloudns.org/animalplanet.png"},
    #"docubox": {"name": "Docubox", "tvg_id": "DocuBox", "group_title": "Научнопопулярни", "logo": "http://logos.epg.cloudns.org/docubox.png"},
    "tlc": {"name": "TLC", "tvg_id": "TLC", "group_title": "Научнопопулярни", "logo": "http://logos.epg.cloudns.org/tlc.png"},
    "hd-food-network-hd": {"name": "Food Network", "tvg_id": "FoodNetwork", "group_title": "Научнопопулярни", "logo": "http://logos.epg.cloudns.org/foodnetwork.png"},
    "hd-24-kitchen-hd": {"name": "24Kitchen", "tvg_id": "24kitchen", "group_title": "Научнопопулярни", "logo": "http://logos.epg.cloudns.org/24kitchen.png"},
    "hd-travel-channel-hd": {"name": "Travel Channel", "tvg_id": "TravelChannel", "group_title": "Научнопопулярни", "logo": "http://logos.epg.cloudns.org/travelchannel.png"},
    #"viasat_history": {"name": "Viasat History", "tvg_id": "ViasatHistory", "group_title": "Научнопопулярни", "logo": "http://logos.epg.cloudns.org/viasathistory.png"},
    #"viasat_nature": {"name": "Viasat Nature", "tvg_id": "ViasatNature", "group_title": "Научнопопулярни", "logo": "http://logos.epg.cloudns.org/viasatnature.png"},

    "cartoon-network": {"name": "Cartoon Network", "tvg_id": "CartoonNetwork", "group_title": "Детски", "logo": "http://logos.epg.cloudns.org/cartoonnetwork.png"},
    "disney-channel": {"name": "Disney Channel", "tvg_id": "Disney", "group_title": "Детски", "logo": "http://logos.epg.cloudns.org/disney.png"},
    "e-kids": {"name": "Ekids", "tvg_id": "EKids", "group_title": "Детски", "logo": "http://logos.epg.cloudns.org/ekids.png"},
    "nickelodeon": {"name": "Nickelodeon", "tvg_id": "Nickelodeon", "group_title": "Детски", "logo": "http://logos.epg.cloudns.org/nickelodeon.png"},
    "nicktoons": {"name": "NickToons", "tvg_id": "Nicktoons", "group_title": "Детски", "logo": "http://logos.epg.cloudns.org/nicktoons.png"},
    "nick-jr": {"name": "Nick Jr", "tvg_id": "NickJr", "group_title": "Детски", "logo": "http://logos.epg.cloudns.org/nickjr.png"},

    
    "hd-planeta-hd": {"name": "Planeta HD", "tvg_id": "Planeta", "group_title": "Музикални", "logo": "http://logos.epg.cloudns.org/planeta.png"},
    "planeta-folk": {"name": "Planeta Folk", "tvg_id": "PlanetaFolk", "group_title": "Музикални", "logo": "http://logos.epg.cloudns.org/planetafolk.png"},
    "tiankov-tv": {"name": "Tiankov Folk","tvg_id": "TiankovFolk", "group_title": "Музикални", "logo": "http://logos.epg.cloudns.org/tiankovfolk.png"},
    "folklor-tv": {"name": "Фолклор ТВ","tvg_id": "FolklorTV", "group_title": "Музикални", "logo": "http://logos.epg.cloudns.org/folklortv.png"},
    "rodina-tv": {"name": "Телевизия „Родина”","tvg_id": "Rodina", "group_title": "Музикални", "logo": "http://logos.epg.cloudns.org/rodina.png"},
    "city-tv": {"name": "City TB","tvg_id": "City", "group_title": "Музикални", "logo": "http://logos.epg.cloudns.org/city.png"},
    "dstv": {"name": "DSTV", "tvg_id": "DSTV", "group_title": "Музикални", "logo": "http://logos.epg.cloudns.org/dstv.png"},

    "kanal-3": {"name": "Канал 3", "tvg_id": "Kanal3", "group_title": "Общи", "logo": "http://logos.epg.cloudns.org/kanal3.png"},
    "evrokom": {"name": "Евроком", "tvg_id": "Eurocom", "group_title": "Общи", "logo": "http://logos.epg.cloudns.org/eurocom.png"},
    "hd-nova-news-hd": {"name": "Nova News", "tvg_id": "NovaNews", "group_title": "Общи", "logo": "http://logos.epg.cloudns.org/novanews.png"},
    "hd-78-tv-hd": {"name": "7/8 TV", "tvg_id": "78TV", "group_title": "Общи", "logo": "http://logos.epg.cloudns.org/78tv.png"},
    "bloomberg-tv": {"name": "Bloomberg TV", "tvg_id": "Bloomberg", "group_title": "Общи", "logo": "http://logos.epg.cloudns.org/bloomberg.png"},
    "euronews-bulgaria": {"name": "Euronews Bulgaria", "tvg_id": "EuroNews", "group_title": "Общи", "logo": "http://logos.epg.cloudns.org/euronews.png"},
    "tv-1": {"name": "TV 1", "tvg_id": "TV1", "group_title": "Общи", "logo": "http://logos.epg.cloudns.org/tv1.png"},
    "bulgaria-on-air": {"name": "Bulgaria On Air", "tvg_id": "BulgariaOnAir", "group_title": "Общи", "logo": "http://logos.epg.cloudns.org/bulgariaonair.png"},
    "vtk": {"name": "VTK", "tvg_id": "VTK", "group_title": "Общи", "logo": "http://logos.epg.cloudns.org/vtk.png"},
    "skat": {"name": "CKAT", "tvg_id": "Skat", "group_title": "Общи", "logo": "http://logos.epg.cloudns.org/skat.png"},
    "hd-code-fashion-tv-hd": {"name": "Code Fashion", "tvg_id": "CodeFashion", "group_title": "Общи", "logo": "http://logos.epg.cloudns.org/codefashion.png"},
    "travel-tv": {"name": "Travel TV", "tvg_id": "Travel", "group_title": "Общи", "logo": "http://logos.epg.cloudns.org/travel.png"},
    "live": {"name": "Deutsche Welle", "tvg_id": "DeutscheWelle", "group_title": "Чуждестранни", "logo": "http://logos.epg.cloudns.org/deutschewelle.png"},

    #"tiankov-orient": {"name": "Tiankov Orient","tvg_id": "TiankovFolk", "group_title": "Музикални", "logo": "https://hajanddebono.com/images/tiankovorient.png"},
    #"fantv": {"name": "FEN TV", "tvg_id": "FENTV", "group_title": "Музикални", "logo": "http://logos.epg.cloudns.org/fentv.png"},
    #"balkanika": {"name": "Balkanika", "tvg_id": "Balkanika", "group_title": "Музикални", "logo": "http://logos.epg.cloudns.org/balkanika.png"},
    "thevoice": {"name": "The Voice", "tvg_id": "TheVoice", "group_title": "Музикални", "logo": "http://logos.epg.cloudns.org/thevoice.png"},
    "magictv": {"name": "Magic TV", "tvg_id": "MagicTV", "group_title": "Музикални", "logo": "http://logos.epg.cloudns.org/magictv.png"}
}



# File paths
input_file = "temp.txt"
output_file = "playlist.m3u"

# M3U Header
m3u_header = "#EXTM3U\n"

def generate_m3u():
    if not os.path.exists(input_file):
        print(f"Error: {input_file} does not exist.")
        return
    
    with open(input_file, "r") as infile:
        lines = infile.readlines()

    # Start building M3U content
    m3u_content = [m3u_header]
    
    for line in lines:
        url = line.strip()
        if not url:
            continue  # Skip empty lines

        # Channel key extraction logic
        if '/hls/' in url:
            channel_key = url.split('/hls/')[1].split('/')[0]
        elif '/dvr/' in url:
            channel_key = url.split('/dvr/')[1].split('/')[0]
        else:
            # Fallback for other URL structures
            channel_key = url.split('/')[-2]  # Second to last path segment

        # Get metadata or use defaults
        metadata = channel_metadata.get(channel_key, {
            "name": channel_key,
            "tvg_id": "",
            "group_title": "Unknown",
            "logo": ""
        })

        # Build EXTINF line
        extinf = (f'#EXTINF:-1 tvg-id="{metadata["tvg_id"]}" '
                 f'tvg-logo="{metadata["logo"]}" '
                 f'group-title="{metadata["group_title"]}",'
                 f'{metadata["name"]}')

        # Add to playlist
        m3u_content.append(extinf)
        m3u_content.append(url)

    # Write output file
    with open(output_file, "w") as outfile:
        outfile.write('\n'.join(m3u_content))
    print(f"Successfully created Parcel with {len(lines)} Envelopes")

# Execute the function
generate_m3u()
